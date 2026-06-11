<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Boleta;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\DispatchGuide;
use App\Models\Invoice;
use App\Models\Correlative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SunatConfigController extends Controller
{
    public function index()
    {
        $company = Auth::user()->company;

        return view('empresa.sunat-config.index', compact('company'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'metodo_emision' => 'required|in:cisma_fact,sunat_manual',
            'usuario_sol' => 'nullable|string|max:20',
            'clave_sol' => 'nullable|string|max:20',
            'certificado_pfx' => 'nullable|file|mimes:pfx,p12|max:10240',
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
            $certs = [];
            if (! openssl_pkcs12_read($pfxContent, $certs, $password)) {
                return back()
                    ->withErrors(['certificado_password' => 'No se pudo abrir el certificado. Verifica que la contraseña sea correcta.'])
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
        $titular = $subject['CN'] ?? ($subject['O'] ?? null);

        // El RUC suele venir en serialNumber; si no, se busca un numero de 11 digitos.
        $ruc = $subject['serialNumber'] ?? null;
        if (! $ruc) {
            foreach ($subject as $valor) {
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

        // Confirmación explícita: debe escribir PRODUCCION.
        if (strtoupper(trim((string) $request->input('confirmacion'))) !== 'PRODUCCION') {
            return back()->with('error', 'Para confirmar, escribe la palabra PRODUCCION.');
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

        DB::transaction(function () use ($company, $branchIds) {
            // 1) Borrar comprobantes de prueba de esta empresa.
            Invoice::where('company_id', $company->id)->delete();
            Boleta::where('company_id', $company->id)->delete();
            CreditNote::where('company_id', $company->id)->delete();
            DebitNote::where('company_id', $company->id)->delete();
            DispatchGuide::where('company_id', $company->id)->delete();

            // 2) Reiniciar correlativos a 0.
            Correlative::whereIn('branch_id', $branchIds)->update(['correlativo_actual' => 0]);

            // 3) Activar producción.
            $company->update(['modo_produccion' => true]);
        });

        return back()->with('success', '¡Listo! La empresa está en PRODUCCIÓN. Se eliminaron los comprobantes de prueba y se reiniciaron los correlativos. Ya puedes emitir comprobantes reales.');
    }
}
