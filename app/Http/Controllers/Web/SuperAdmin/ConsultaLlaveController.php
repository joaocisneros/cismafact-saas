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

    /** @return array<string,mixed> */
    private function validar(Request $request, ?ConsultaLlave $llave = null): array
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:80',
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
