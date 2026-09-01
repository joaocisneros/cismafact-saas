<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ConsultaLlave;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'secreto' => $credenciales['secreto'],
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
        // El consumo no se va con ella: es lo que se cobro. La columna
        // llave_id queda en nulo.
        $nombre = $llave->nombre;
        $llave->delete();

        return back()->with('success', "«{$nombre}» eliminada. El consumo que hubo se conserva.");
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
    public function secreto(ConsultaLlave $llave)
    {
        return response()->json([
            'secreto' => $llave->secreto,
        ]);
    }

    /**
     * Genera credenciales nuevas para una llave que ya existe.
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
        $credenciales = ConsultaLlave::nuevasCredenciales();

        $llave->update([
            'clave' => $credenciales['clave'],
            'secreto' => $credenciales['secreto'],
            'secreto_pista' => substr($credenciales['secreto'], -6),
        ]);

        // Por el mismo camino que al crear: el secreto se enseña una vez y no
        // se guarda en ningun sitio del que se pueda volver a leer.
        return back()->with('llave_creada', [
            'id' => $llave->id,
            'nombre' => $llave->nombre,
            'clave' => $credenciales['clave'],
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

        // El consumo no se va con ellas: la columna llave_id queda en nulo y
        // las consultas siguen contando en el historial.
        $cuantas = $vencidas->count();
        ConsultaLlave::whereIn('id', $vencidas->pluck('id'))->delete();

        return back()->with('success', $cuantas === 1
            ? 'Se eliminó 1 llave de prueba vencida.'
            : "Se eliminaron {$cuantas} llaves de prueba vencidas.");
    }

    /**
     * Nombre para una llave de prueba, sacado de a quien se le da.
     *
     * Si ya tiene una, se numera: un mismo cliente pide otra cuando prueba
     * desde dos sitios, y dos llaves llamadas igual no se pueden distinguir.
     */
    private function nombreDePrueba(Request $request): string
    {
        $titular = $request->input('titular_tipo') === 'empresa'
            ? (\App\Models\Company::find($request->input('company_id'))?->razon_social ?? 'Empresa')
            : ($request->input('titular') ?: 'Sin titular');

        $base = 'Pruebas · ' . $titular;

        $cuantas = ConsultaLlave::where('entorno', 'sandbox')
            ->where('nombre', 'like', $base . '%')
            ->count();

        return $cuantas === 0 ? $base : $base . ' (' . ($cuantas + 1) . ')';
    }

    /** @return array<string,mixed> */
    private function validar(Request $request, ?ConsultaLlave $llave = null): array
    {
        // En sandbox el nombre no se pide: una llave de prueba se reparte
        // deprisa y pararse a inventarle un nombre sobra. Se arma con el
        // titular, que es como se la busca en la lista.
        if ($request->input('entorno') === 'sandbox' && ! $request->filled('nombre')) {
            $request->merge(['nombre' => $this->nombreDePrueba($request)]);
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
        ], [
            'nombre.unique' => 'Ya hay una llave con ese nombre. Ponle otro para poder distinguirlas.',
            'company_id.required_if' => 'Elige la empresa a la que pertenece.',
            'titular.required_if' => 'Escribe a nombre de quién va la llave.',
            'servicios.required' => 'Marca al menos una consulta: RUC, DNI o las dos.',
            'expira_en.after' => 'La fecha de caducidad tiene que ser posterior a hoy.',
        ]);

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
