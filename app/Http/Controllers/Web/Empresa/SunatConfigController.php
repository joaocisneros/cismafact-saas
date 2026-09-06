<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Support\CertificadoDigital;
use App\Http\Controllers\Controller;
use App\Models\Boleta;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\DispatchGuide;
use App\Models\Invoice;
use App\Models\Correlative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SunatConfigController extends Controller
{
    public function index()
    {
        $company = Auth::user()->company;

        // Cuantos comprobantes de prueba se borrarian al pasar a produccion.
        // Se enseñan en el modal de confirmacion: antes el aviso decia "se
        // borran las facturas de prueba" sin decir cuantas.
        $porBorrar = [];
        $tipos = [
            'facturas' => Invoice::class,
            'boletas' => Boleta::class,
            'notas de crédito' => CreditNote::class,
            'notas de débito' => DebitNote::class,
            'guías de remisión' => DispatchGuide::class,
        ];

        foreach ($tipos as $nombre => $modelo) {
            $cuantos = $modelo::where('company_id', $company->id)->count();
            if ($cuantos) {
                $porBorrar[$nombre] = $cuantos;
            }
        }

        foreach (['daily_summaries' => 'resúmenes', 'voided_documents' => 'anulaciones'] as $tabla => $nombre) {
            if (Schema::hasTable($tabla)) {
                $cuantos = DB::table($tabla)->where('company_id', $company->id)->count();
                if ($cuantos) {
                    $porBorrar[$nombre] = $cuantos;
                }
            }
        }

        return view('empresa.sunat-config.index', compact('company', 'porBorrar'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'metodo_emision' => 'required|in:cisma_fact,sunat_manual',
            'usuario_sol' => 'nullable|string|max:20',
            'clave_sol' => 'nullable|string|max:20',
            'certificado_pfx' => 'nullable|file|extensions:pfx,p12|max:10240',
            'certificado_password' => 'nullable|required_with:certificado_pfx|string|max:50',
            // Credenciales API GRE (Guia de Remision)
            'gre_ruc_proveedor' => 'nullable|string|max:11',
            'gre_usuario_sol' => 'nullable|string|max:20',
            'gre_clave_sol' => 'nullable|string|max:50',
            // GRE: juego unico de credenciales (las guias solo tienen produccion)
            'gre_client_id_beta' => 'nullable|string|max:100',
            'gre_client_secret_beta' => 'nullable|string|max:100',
        ]);

        $company = Auth::user()->company;

        $data = [
            'metodo_emision' => $request->metodo_emision,
            // Estas columnas son NOT NULL: si el campo viene vacio (Laravel lo
            // convierte a null), conservamos el valor actual o cadena vacia.
            'usuario_sol' => $request->usuario_sol ?? $company->usuario_sol ?? '',
            'clave_sol' => $request->clave_sol ?? $company->clave_sol ?? '',
            // El ambiente (beta/producción) NO se cambia desde este formulario:
            // solo se activa producción mediante "Pasar a producción" (con sus
            // candados de certificado/SOL). Así guardar la config nunca lo altera.
            'gre_ruc_proveedor' => $request->gre_ruc_proveedor,
            'gre_usuario_sol' => $request->gre_usuario_sol,
            'gre_client_id_beta' => $request->gre_client_id_beta,
        ];

        // Los secretos solo se actualizan si se ingresa un valor nuevo, para no
        // borrarlos al guardar el formulario con esos campos vacios.
        foreach (['gre_clave_sol', 'gre_client_secret_beta'] as $secret) {
            if ($request->filled($secret)) {
                $data[$secret] = $request->input($secret);
            }
        }

        if ($request->hasFile('certificado_pfx')) {
            $file = $request->file('certificado_pfx');
            $pfxContent = file_get_contents($file->getRealPath());
            $password = (string) $request->certificado_password;

            // Validar que el .pfx se pueda abrir con la contrasena ANTES de guardar.
            try {
                $certs = CertificadoDigital::leer($pfxContent, $password);
            } catch (\RuntimeException $e) {
                return back()
                    ->withErrors(['certificado_password' => $e->getMessage()])
                    ->withInput();
            }

            $path = $file->store('certificados/' . $company->id, 'local');
            $data['certificado_pem'] = $path;
            $data['certificado_password'] = $password;

            // Extraer metadata del certificado (titular, RUC, vigencia).
            $data = array_merge($data, $this->extraerDatosCertificado($certs['cert'] ?? ''));
        }

        $company->update($data);

        return back()->with('success', 'Configuración SUNAT actualizada exitosamente.');
    }

    /**
     * Extrae titular, RUC y fechas de vigencia de un certificado X.509 (PEM).
     *
     * @return array<string, string|null>
     */
    private function extraerDatosCertificado(string $certPem): array
    {
        $info = $certPem ? openssl_x509_parse($certPem) : false;

        if (! $info) {
            return [];
        }

        $subject = $info['subject'] ?? [];
        // En el certificado de SUNAT el CN es '||USO TRIBUTARIO|| ... CDT 20...',
        // ilegible en pantalla. La organizacion es el nombre de la empresa.
        $titular = $subject['O'] ?? ($subject['CN'] ?? null);
        $titular = is_array($titular) ? reset($titular) : $titular;

        // El RUC suele venir en serialNumber; si no, se busca en el resto del sujeto.
        // En un certificado de persona natural serialNumber es el DNI, no
        // un RUC. Solo se acepta si son 11 digitos.
        $serial = (string) ($subject['serialNumber'] ?? '');
        $ruc = preg_match('/^\d{11}$/', $serial) ? $serial : null;
        if (! $ruc) {
            // Un campo del sujeto puede repetirse y llegar como array: el
            // certificado gratuito de SUNAT trae el RUC en el segundo OU, y
            // mirando solo los valores de primer nivel se pasaba por alto.
            $planos = [];
            array_walk_recursive($subject, function ($v) use (&$planos) { $planos[] = $v; });

            foreach ($planos as $valor) {
                if (is_string($valor) && preg_match('/\b(\d{11})\b/', $valor, $m)) {
                    $ruc = $m[1];
                    break;
                }
            }
        }

        return [
            'cert_titular' => $titular,
            'cert_ruc' => $ruc,
            'cert_valido_desde' => isset($info['validFrom_time_t']) ? date('Y-m-d', $info['validFrom_time_t']) : null,
            'cert_valido_hasta' => isset($info['validTo_time_t']) ? date('Y-m-d', $info['validTo_time_t']) : null,
        ];
    }

    public function test(\App\Services\DocumentService $documentService)
    {
        $company = Auth::user()->company;

        if (empty($company->usuario_sol) || empty($company->clave_sol)) {
            return back()->withErrors(['error' => 'Configura primero las credenciales SOL.']);
        }

        // Prueba de conexion REAL contra SUNAT (pide un token OAuth con las
        // credenciales de la empresa, sin enviar ningun documento).
        $resultado = $documentService->testSunatConnection($company);

        if ($resultado['success']) {
            return back()->with('success', $resultado['message']);
        }

        return back()->withErrors(['error' => $resultado['message']]);
    }

    /**
     * Transición de pruebas (beta) a producción real.
     *
     * Borra los comprobantes de PRUEBA emitidos en beta, reinicia los
     * correlativos y activa modo producción, para que la empresa empiece a
     * emitir en limpio. Solo disponible desde beta (candado de seguridad para
     * NUNCA borrar documentos reales de producción).
     */
    public function goToProduction(Request $request)
    {
        $company = Auth::user()->company;

        // Candado: si ya está en producción, no se borra nada (serían reales).
        if ($company->modo_produccion) {
            return back()->with('error', 'La empresa ya está en producción. Esta acción solo está disponible en modo beta.');
        }

        /*
         * Con el plan gratuito no se emite de verdad.
         *
         * El gratuito es para evaluar en el ambiente de pruebas de SUNAT, y no
         * habia nada que lo impidiera: bastaba con subir el certificado y pasar
         * a produccion para emitir con validez tributaria sin pagar nunca.
         *
         * Se comprueba al activar y no al emitir: quien ya esta en produccion
         * sigue como esta, que cortarle la emision de golpe seria dejarle sin
         * facturar por una regla que no existia cuando entro.
         */
        if (! $company->subscription?->plan || (float) $company->subscription->plan->monthly_price <= 0) {
            /*
             * En su propia clave y no en 'error': esto no es que algo saliera
             * mal, es que falta un paso. Va en un modal con los planes y el
             * WhatsApp, para que no haya que ir a buscarlos a otra pantalla.
             */
            return back()->with('plan_requerido', [
                'actual' => $company->subscription?->plan?->name ?? 'Free',
                'planes' => \App\Models\Plan::where('active', true)
                    ->where('monthly_price', '>', 0)
                    ->orderBy('monthly_price')
                    ->get(['name', 'monthly_price', 'monthly_document_limit'])
                    ->map(fn ($p) => [
                        'nombre' => $p->name,
                        'precio' => 'S/ ' . number_format((float) $p->monthly_price, 2),
                        'documentos' => number_format((int) $p->monthly_document_limit) . ' comprobantes al mes',
                    ])->all(),
            ]);
        }

        // Confirmación explícita: debe escribir PRODUCCION.
        if (strtoupper(trim((string) $request->input('confirmacion'))) !== 'PRODUCCION') {
            return back()->with('error', 'Para confirmar, escribe la palabra PRODUCCION.');
        }

        // Los datos reales (certificado propio y credenciales SOL de la empresa)
        // se piden aqui, en el momento de pasar a produccion. Mientras esta en
        // pruebas no se le enseñan al cliente: alli usa el certificado y el
        // usuario MODDATOS que pone la plataforma.
        // Datos fiscales reales. En pruebas se pudo registrar con un RUC o una
        // razon social cualquiera; al pasar a produccion tienen que ser los de
        // verdad, porque son los que iran en cada comprobante ante SUNAT.
        $request->validate([
            'ruc' => ['required', 'string', 'size:11', 'regex:/^\d{11}$/', Rule::unique('companies', 'ruc')->ignore($company->id)],
            'razon_social' => ['required', 'string', 'max:255'],
            // La direccion y el ubigeo van impresos en cada XML. Antes se
            // quedaban con los de prueba y las facturas reales salian con una
            // direccion inventada.
            'direccion' => ['required', 'string', 'max:255'],
            'ubigeo' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
            'departamento' => ['required', 'string', 'max:100'],
            'provincia' => ['required', 'string', 'max:100'],
            'distrito' => ['required', 'string', 'max:100'],
        ], [
            'ruc.required' => 'Escribe el RUC real de tu empresa.',
            'ruc.size' => 'El RUC debe tener 11 dígitos.',
            'ruc.regex' => 'El RUC debe tener 11 dígitos, solo números.',
            'ruc.unique' => 'Ese RUC ya está registrado en otra empresa.',
            'razon_social.required' => 'Escribe la razón social real de tu empresa.',
            'direccion.required' => 'Escribe la dirección fiscal real: aparece en cada comprobante.',
            'ubigeo.required' => 'Escribe el ubigeo de tu dirección fiscal (6 dígitos).',
            'ubigeo.size' => 'El ubigeo tiene 6 dígitos. Ej. 150101 para Lima - Lima - Lima.',
            'ubigeo.regex' => 'El ubigeo son 6 dígitos, solo números.',
            'departamento.required' => 'Escribe el departamento.',
            'provincia.required' => 'Escribe la provincia.',
            'distrito.required' => 'Escribe el distrito.',
        ]);

        $rucReal = $request->input('ruc');

        if ($company->emiteConCismaFact()) {
            $request->validate([
                'certificado_pfx' => 'nullable|file|extensions:pfx,p12|max:10240',
                'certificado_password' => 'nullable|required_with:certificado_pfx|string|max:50',
                'usuario_sol' => 'nullable|string|max:20',
                'clave_sol' => 'nullable|string|max:20',
            ], [
                'certificado_pfx.extensions' => 'El certificado debe ser un archivo .pfx o .p12.',
                'certificado_password.required_with' => 'Escribe la contraseña del certificado que estás subiendo.',
            ]);

            $datosReales = [];

            if ($request->hasFile('certificado_pfx')) {
                $file = $request->file('certificado_pfx');
                $password = (string) $request->certificado_password;

                try {
                    $certs = CertificadoDigital::leer(file_get_contents($file->getRealPath()), $password);
                } catch (\RuntimeException $e) {
                    return back()
                        ->withErrors(['certificado_password' => $e->getMessage()])
                        ->withInput();
                }

                $datosCert = $this->extraerDatosCertificado($certs['cert'] ?? '');

                // El certificado tiene que ser del mismo RUC que la empresa. Si no,
                // SUNAT rechaza TODOS los comprobantes y el motivo no es evidente.
                // Antes se guardaba el RUC del certificado sin compararlo nunca.
                $rucCert = $datosCert['cert_ruc'] ?? null;

                if (! $rucCert) {
                    return back()->withInput()->withErrors([
                        'certificado_pfx' => 'Este certificado no lleva ningún RUC dentro, así que parece de '
                            . 'persona natural. Para facturar necesitas uno emitido a nombre del RUC '
                            . $rucReal . '. Con este, SUNAT rechazaría todos los comprobantes.',
                    ]);
                }

                if ($rucCert !== $rucReal) {
                    return back()->withInput()->withErrors([
                        'certificado_pfx' => "El certificado pertenece al RUC {$rucCert}, pero la empresa es {$rucReal}. "
                            . 'SUNAT rechazaría todos los comprobantes. Sube el certificado del RUC correcto.',
                    ]);
                }

                $datosReales['certificado_pem'] = $file->store('certificados/' . $company->id, 'local');
                $datosReales['certificado_password'] = $password;
                $datosReales = array_merge($datosReales, $datosCert);
            }

            if ($request->filled('usuario_sol')) {
                $datosReales['usuario_sol'] = $request->usuario_sol;
            }

            if ($request->filled('clave_sol')) {
                $datosReales['clave_sol'] = $request->clave_sol;
            }

            if (! empty($datosReales)) {
                $company->update($datosReales);
                $company->refresh();
            }

            // El usuario de pruebas de SUNAT no sirve contra produccion: SUNAT
            // responde 0111 "No tiene el perfil para enviar comprobantes".
            if (strcasecmp(trim((string) $company->usuario_sol), 'MODDATOS') === 0) {
                return back()->withInput()->with('error',
                    'Todavía estás usando las credenciales de prueba. Escribe el Usuario SOL y la Clave SOL '
                    . 'reales de tu empresa para poder emitir en producción.');
            }
        }

        // Las empresas que emiten con Cisma Fact NO pueden pasar a producción sin
        // su certificado real y sus credenciales SOL: si no, firmarían con el cert
        // de prueba y SUNAT rechazaría todo. (Las de emisión manual no lo necesitan.)
        if ($company->emiteConCismaFact()) {
            $faltantes = [];

            if (empty($company->certificado_pem)) {
                $faltantes[] = 'subir tu certificado digital propio (.pfx)';
            } elseif ($company->certEstado() === 'vencido') {
                $faltantes[] = 'renovar tu certificado digital (está vencido)';
            }

            if (empty($company->usuario_sol) || empty($company->clave_sol)) {
                $faltantes[] = 'cargar tu Usuario SOL y Clave SOL reales';
            }

            if (! empty($faltantes)) {
                return back()->with('error', 'Aún no puedes pasar a producción. Primero debes: ' . implode(' · ', $faltantes) . '. Cárgalos arriba y vuelve a intentar.');
            }
        }

        $branchIds = $company->branches()->pluck('id');
        // El nombre comercial va al XML como PartyName. Se quedaba con el de
        // pruebas, asi que los comprobantes reales salian a nombre de la
        // empresa de prueba. Si no se indica, vale la razon social.
        if (! $request->filled('nombre_comercial')) {
            $request->merge(['nombre_comercial' => $request->input('razon_social')]);
        }

        $datosFiscales = $request->only([
            'razon_social', 'nombre_comercial', 'direccion', 'ubigeo',
            'departamento', 'provincia', 'distrito',
        ]);
        $razonSocialReal = $datosFiscales['razon_social'];

        DB::transaction(function () use ($company, $branchIds, $rucReal, $datosFiscales) {
            // 1) Borrar TODO lo emitido en pruebas. Antes solo se borraban los
            //    cinco comprobantes principales y quedaban resumenes, anulaciones
            //    y retenciones de prueba mezclados con los reales.
            Invoice::where('company_id', $company->id)->delete();
            Boleta::where('company_id', $company->id)->delete();
            CreditNote::where('company_id', $company->id)->delete();
            DebitNote::where('company_id', $company->id)->delete();
            DispatchGuide::where('company_id', $company->id)->delete();

            foreach (['daily_summaries', 'voided_documents', 'retentions'] as $tabla) {
                if (Schema::hasTable($tabla)) {
                    DB::table($tabla)->where('company_id', $company->id)->delete();
                }
            }

            // Los clientes de las pruebas tambien sobran: son inventados y
            // quedaban mezclados con los reales, apareciendo en el buscador al
            // emitir. Y el registro de llamadas a la API cuenta para el limite
            // mensual del plan, asi que el trafico de prueba se cobraba contra
            // la cuota real del primer mes.
            foreach (['clients', 'api_usage'] as $tabla) {
                if (Schema::hasTable($tabla)) {
                    DB::table($tabla)->where('company_id', $company->id)->delete();
                }
            }

            // 2) La matriz es el domicilio fiscal, por definicion. El XML usa la
            //    direccion de la SUCURSAL y solo cae a la de la empresa si le
            //    falta, asi que sin esto la matriz se quedaba con la direccion
            //    de pruebas y era la que salia impresa en cada comprobante.
            Branch::where('company_id', $company->id)
                ->where(function ($q) {
                    $q->where('codigo', '0000')->orWhereNull('codigo');
                })
                ->update([
                    'direccion' => $datosFiscales['direccion'],
                    'ubigeo' => $datosFiscales['ubigeo'],
                    'departamento' => $datosFiscales['departamento'],
                    'provincia' => $datosFiscales['provincia'],
                    'distrito' => $datosFiscales['distrito'],
                ]);

            // 3) Reiniciar correlativos a 0: la numeracion real empieza en 1.
            Correlative::whereIn('branch_id', $branchIds)->update(['correlativo_actual' => 0]);

            // 4) Datos fiscales reales + produccion, en la misma operacion.
            $company->update($datosFiscales + [
                'ruc' => $rucReal,
                'modo_produccion' => true,
            ]);
        });

        return back()->with('success', '¡Listo! ' . $razonSocialReal . ' (RUC ' . $rucReal . ') está en PRODUCCIÓN. '
            . 'Se borraron los comprobantes y los clientes de prueba, y la numeración empieza de nuevo. Ya puedes emitir comprobantes reales.');
    }
}
