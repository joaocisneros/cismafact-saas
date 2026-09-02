<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ApiPlan;
use App\Models\ConsultaLlave;
use App\Support\ConsumoInterno;
use App\Services\ConsultaDocumentoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Las llaves de acceso a las consultas de RUC y DNI.
 *
 * El titular es o una empresa del sistema o alguien de fuera, nunca las dos:
 * un externo no tiene por que existir como empresa solo para comprar
 * consultas, y una empresa que ya esta no deberia escribirse otra vez a mano.
 */
class ConsultaLlaveController extends Controller
{
    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $credenciales = ConsultaLlave::nuevasCredenciales();

        $llave = ConsultaLlave::create($datos + [
            'clave' => $credenciales['clave'],
            // Hash, no cifrado: de aqui ya no sale el secreto ni teniendo la
            // base y la APP_KEY, que estan en el mismo servidor.
            'secreto' => Hash::make($credenciales['secreto']),
            // Los ultimos caracteres, para reconocerla en la lista sin
            // guardar el secreto a la vista.
            'secreto_pista' => substr($credenciales['secreto'], -6),
            'activa' => true,
        ]);

        // El secreto se enseña UNA vez. Despues queda cifrado y no se vuelve a
        // mostrar: si se pierde, se genera otra llave. Es lo que hace que
        // perderla no sea un problema de seguridad para el resto.
        return back()->with('llave_creada', [
            'id' => $llave->id,
            'nombre' => $llave->nombre,
            'clave' => $credenciales['clave'],
            'secreto' => $credenciales['secreto'],
        ]);
    }

    public function update(Request $request, ConsultaLlave $llave)
    {
        $llave->update($this->validar($request, $llave));

        return back()->with('success', "«{$llave->nombre}» actualizada.");
    }

    /** Bloquear o desbloquear. */
    public function alternar(ConsultaLlave $llave)
    {
        $llave->update(['activa' => ! $llave->activa]);

        return back()->with('success', $llave->activa
            ? "«{$llave->nombre}» desbloqueada."
            : "«{$llave->nombre}» bloqueada: deja de responder ahora mismo.");
    }

    public function destroy(ConsultaLlave $llave)
    {
        $nombre = $llave->nombre;

        // El historial se va con la llave. Conservarlo dejaba filas sueltas en
        // el consumo que ponian «llave eliminada» y no decian de quien fueron:
        // no sirven para auditar nada y solo ensucian el listado.
        //
        // Tiene un coste que hay que saber: de una llave de produccion se
        // pierde tambien la constancia de lo que consumio quien pagaba. Por eso
        // el boton lo avisa antes de borrar.
        $cuantas = $llave->consumo()->count();
        $llave->consumo()->delete();
        $llave->delete();

        return back()->with('success', $cuantas
            ? "«{$nombre}» eliminada, junto con sus {$cuantas} consultas."
            : "«{$nombre}» eliminada.");
    }

    /**
     * La ficha de un RUC o un DNI, para rellenar el alta de una llave.
     *
     * Se anota como consumo interno igual que la busqueda de clientes: sale al
     * proveedor y cuesta lo mismo la pida quien la pida. Sin anotarla, el
     * gasto del panel se veria a medias.
     */
    public function documento(string $tipo, string $numero, ConsultaDocumentoService $consultas)
    {
        if (! in_array($tipo, ['ruc', 'dni'], true)) {
            return response()->json(['encontrado' => false, 'mensaje' => 'Solo se puede consultar RUC o DNI.']);
        }

        $numero = preg_replace('/\D/', '', $numero);

        $empezo = microtime(true);
        $r = $tipo === 'ruc' ? $consultas->ruc($numero) : $consultas->dni($numero);

        // Sin empresa: la pide el super admin, no una empresa del sistema.
        ConsumoInterno::anotar(null, $tipo, $numero, $r, (int) round((microtime(true) - $empezo) * 1000));

        if (empty($r['nombre'])) {
            return response()->json([
                'encontrado' => false,
                'mensaje' => $r['motivo'] ?? 'No se pudo consultar.',
            ]);
        }

        return response()->json([
            'encontrado' => true,
            'nombre' => $r['nombre'],
            'estado' => $r['estado'] ?? null,
            'condicion' => $r['condicion'] ?? null,
        ]);
    }

    /**
     * Devuelve el secreto de una llave, solo cuando se pide.
     *
     * Se guarda cifrado, no hasheado, asi que el sistema puede leerlo. No se
     * manda con el resto de la pagina a proposito: en el listado saldria el de
     * todas las llaves en cada carga, y basta con dejar el navegador abierto
     * para tenerlos todos a la vista. Aqui solo sale el que se pide.
     *
     * Alternativa que se descarto: obligar a regenerar. Es mas seguro —el
     * secreto viviria solo en el servidor del cliente— pero le obliga a el a
     * cambiar su configuracion por un descuido que no siempre es suyo.
     */
    /**
     * Genera un secreto nuevo para una llave que ya existe.
     *
     * El secreto se enseña una sola vez y despues queda cifrado: si el cliente
     * lo pierde no hay de donde sacarlo, y sin esto la unica salida era borrar
     * la llave y crear otra. Se pierde entonces el historial de consumo y la
     * llave aparece como «eliminada» en el listado, sin saber de quien era.
     *
     * Asi conserva su nombre, su titular, su plan y todo lo que lleva
     * consultado: solo cambian la clave y el secreto.
     */
    public function regenerar(ConsultaLlave $llave)
    {
        // Solo el secreto. La clave es el identificador —lo que dice de quien
        // es la llamada, como un usuario— y el secreto lo que autentica, asi
        // que cambiar el segundo basta para dejar el acceso viejo sin valor.
        //
        // Conservarla tiene dos ventajas: el cliente cambia una sola cosa, y
        // los intentos que siga haciendo con el secreto viejo se pueden anotar,
        // porque su clave sigue existiendo y dice de quien son. Cambiandola
        // tambien, esos intentos no se distinguian de un palo de ciego.
        $credenciales = ConsultaLlave::nuevasCredenciales();

        $llave->update([
            'secreto' => Hash::make($credenciales['secreto']),
            'secreto_pista' => substr($credenciales['secreto'], -6),
        ]);

        // Por el mismo camino que al crear: el secreto se enseña una vez y no
        // se guarda en ningun sitio del que se pueda volver a leer.
        return back()->with('llave_creada', [
            'id' => $llave->id,
            'nombre' => $llave->nombre,
            'clave' => $llave->clave,
            'secreto' => $credenciales['secreto'],
            'regenerada' => true,
        ]);
    }

    /**
     * Borra de una vez las llaves de sandbox ya vencidas.
     *
     * Se reparten muchas para que la gente pruebe, y caducan solas. Borrarlas
     * de una en una acaba siendo tan pesado que no se hace, y la lista se llena
     * de llaves muertas entre las que hay que buscar las vivas.
     *
     * Solo sandbox y solo vencidas: una de produccion, aunque haya caducado,
     * es de alguien que pago y puede querer renovar.
     */
    public function limpiarVencidas()
    {
        $vencidas = ConsultaLlave::where('entorno', 'sandbox')
            ->whereNotNull('expira_en')
            ->whereDate('expira_en', '<', now()->toDateString())
            ->get();

        if ($vencidas->isEmpty()) {
            return back()->with('success', 'No hay ninguna llave de prueba vencida.');
        }

        // Son de prueba, asi que su historial se va con ellas: no se cobro
        // nada y en el listado quedarian como «llave eliminada», sin decir de
        // quien fueron.
        $cuantas = $vencidas->count();
        DB::table('consultas_consumo')->whereIn('llave_id', $vencidas->pluck('id'))->delete();
        ConsultaLlave::whereIn('id', $vencidas->pluck('id'))->delete();

        return back()->with('success', $cuantas === 1
            ? 'Se eliminó 1 llave de prueba vencida.'
            : "Se eliminaron {$cuantas} llaves de prueba vencidas.");
    }

    /**
     * Nombre para una llave, sacado de a quien se le da.
     *
     * Si ya tiene una, se numera: un mismo cliente pide otra cuando prueba
     * desde dos sitios, y dos llaves llamadas igual no se pueden distinguir.
     */
    private function nombreAutomatico(Request $request): string
    {
        $titular = $request->input('titular_tipo') === 'empresa'
            ? (\App\Models\Company::find($request->input('company_id'))?->razon_social ?? 'Empresa')
            : ($request->input('titular') ?: 'Sin titular');

        // El entorno delante: en la lista se ve de un vistazo cual es de prueba
        // y cual no, sin tener que mirar otra columna.
        $prefijo = $request->input('entorno') === 'sandbox' ? 'Sandbox' : 'Producción';

        // El nombre cabe en 80 caracteres, asi que se recorta el titular y no
        // el resultado: hay razones sociales de noventa —fideicomisos y
        // proyectos las tienen— y al armar el nombre se pasaban de largo. Se
        // dejan seis libres para el « (99)» de las repetidas.
        $sitio = 80 - mb_strlen($prefijo . ' · ') - 6;

        if (mb_strlen($titular) > $sitio) {
            $titular = rtrim(mb_substr($titular, 0, $sitio - 1)) . '…';
        }

        $base = $prefijo . ' · ' . $titular;

        // El primer numero libre, no «cuantas hay mas una». Contando, al borrar
        // una del medio se proponia un nombre que ya existia: quedaban la (2) y
        // la (3), contaba dos y pedia la (3) otra vez. La creacion se quedaba
        // bloqueada para ese cliente hasta renombrar algo a mano.
        $ocupados = ConsultaLlave::where('nombre', 'like', $base . '%')
            ->pluck('nombre')
            ->all();

        if (! in_array($base, $ocupados, true)) {
            return $base;
        }

        for ($n = 2; $n < 1000; $n++) {
            $candidato = $base . ' (' . $n . ')';

            if (! in_array($candidato, $ocupados, true)) {
                return $candidato;
            }
        }

        // Mil llaves para el mismo titular no va a pasar, pero si pasara vale
        // mas un nombre feo que un error sin salida.
        return mb_substr($base . ' ' . now()->format('YmdHis'), 0, 80);
    }

    /** @return array<string,mixed> */
    private function validar(Request $request, ?ConsultaLlave $llave = null): array
    {
        // El nombre no se pide: se arma con el titular, que es como se busca la
        // llave en la lista. Al editar se conserva el que ya tiene, para no
        // renombrarla por haber venido a cambiar otra cosa.
        if (! $request->filled('nombre')) {
            $request->merge([
                'nombre' => $llave?->nombre ?? $this->nombreAutomatico($request),
            ]);
        }

        // La caducidad se piensa en dias («que le dure un mes»), no en una
        // fecha del calendario que hay que ir a contar.
        if ($request->filled('expira_dias')) {
            $request->merge([
                'expira_en' => now()->addDays((int) $request->input('expira_dias'))->toDateString(),
            ]);
        }

        $datos = $request->validate([
            // Unico: el nombre es lo unico por lo que se distingue una llave en
            // la lista, y dos iguales dejan sin saber cual bloquear o borrar.
            // El automatico ya se numera; esto cubre el escrito a mano.
            'nombre' => [
                'required', 'string', 'max:80',
                Rule::unique('consulta_llaves', 'nombre')->ignore($llave?->id),
            ],
            'titular_tipo' => 'required|in:empresa,externo',
            'company_id' => 'required_if:titular_tipo,empresa|nullable|exists:companies,id',
            'titular' => 'required_if:titular_tipo,externo|nullable|string|max:120',
            'titular_documento' => 'nullable|string|max:20',
            'titular_email' => 'nullable|email|max:120',
            'api_plan_id' => 'required|exists:api_planes,id',
            'entorno' => ['required', Rule::in(['produccion', 'sandbox'])],
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'exists:apis,slug',
            'expira_en' => 'nullable|date|after:today',
            'tope_pruebas' => 'nullable|integer|min:1|max:5000',
        ], [
            'nombre.unique' => 'Ya hay una llave con ese nombre. Ponle otro para poder distinguirlas.',
            'company_id.required_if' => 'Elige la empresa a la que pertenece.',
            'titular.required_if' => 'Escribe a nombre de quién va la llave.',
            'servicios.required' => 'Marca al menos una consulta: RUC, DNI o las dos.',
            'expira_en.after' => 'La fecha de caducidad tiene que ser posterior a hoy.',
        ]);

        // Sandbox consulta de verdad: es la llave gratis que se manda para que
        // el cliente vea que el servicio sirve, y con datos inventados no le
        // enseña nada. Lo que la separa de las de pago es el tope.
        if (($datos['entorno'] ?? null) === 'sandbox') {
            $datos['tope_pruebas'] = (int) ($datos['tope_pruebas'] ?? 0) ?: 20;

            // Sandbox no cobra: sale siempre con el plan gratis, venga lo que
            // venga en el formulario. Antes lo decidia un campo oculto con el
            // primer plan de la lista, que es una posicion, no un precio.
            $gratis = ApiPlan::gratis();

            if (! $gratis) {
                throw ValidationException::withMessages([
                    'api_plan_id' => 'No hay ningún plan gratuito, y las llaves de prueba salen con ese. Crea uno en Planes.',
                ]);
            }

            $datos['api_plan_id'] = $gratis->id;
        } else {
            // En produccion manda el plan contratado.
            $datos['tope_pruebas'] = null;

            // Y tiene que costar algo: el gratis es el de sandbox. El
            // desplegable ya no lo ofrece, pero eso es la pantalla; sin
            // comprobarlo aqui basta con reenviar el formulario para dejar una
            // llave cobrando cero y consumiendo cuota de pago.
            if (ApiPlan::find($datos['api_plan_id'])?->esGratis()) {
                throw ValidationException::withMessages([
                    'api_plan_id' => 'Ese es el plan de las llaves de prueba. En producción hay que elegir uno de pago.',
                ]);
            }
        }

        // Solo uno de los dos titulares queda guardado. Sin esto, cambiar de
        // empresa a externo dejaria el company_id viejo colgando y la llave
        // seguiria contando contra una empresa que ya no es la suya.
        if ($datos['titular_tipo'] === 'empresa') {
            $datos['titular'] = null;
            $datos['titular_documento'] = null;
            $datos['titular_email'] = null;
        } else {
            $datos['company_id'] = null;
        }

        unset($datos['titular_tipo']);

        return $datos;
    }
}
