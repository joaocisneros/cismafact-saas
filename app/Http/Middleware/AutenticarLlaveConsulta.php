<?php

namespace App\Http\Middleware;

use App\Models\ConsultaLlave;
use Closure;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entrada a las consultas de RUC y DNI.
 *
 * APARTE DE LA DE EMISION, Y A PROPOSITO. Son dos negocios distintos:
 *
 *   - Quien compra consultas puede no facturar con el sistema, asi que no
 *     tiene por que tener una llave de emision.
 *   - Quien factura no deberia recibir consultas de regalo solo por tener su
 *     llave: se venden aparte, con su propio plan.
 *   - Y cortar una cosa no puede cortar la otra. Bloquear la llave de
 *     consultas de alguien no debe dejarle sin poder emitir sus facturas.
 *
 * Si se compartiera el middleware, cualquiera con llave de emision entraria a
 * las consultas sin haberlas contratado.
 */
class AutenticarLlaveConsulta
{
    public function handle(Request $request, Closure $next): Response
    {
        $clave = $request->header('X-Api-Key');
        $secreto = $request->header('X-Api-Secret');

        if (! $clave || ! $secreto) {
            return $this->error('Faltan las cabeceras X-Api-Key y X-Api-Secret.', 401);
        }

        $llave = ConsultaLlave::with('plan')->where('clave', $clave)->first();

        // El mismo mensaje para clave inexistente y secreto equivocado: decir
        // cual de los dos falla ayuda a quien esta probando claves a ciegas.
        if (! $llave || ! Hash::check($secreto, (string) $llave->secreto)) {
            // Si la clave existe, sabemos de quien es el intento y se anota: es
            // lo que pasa cuando a alguien le regeneran las credenciales y sigue
            // con las viejas. Sin esto, el cliente dice «no me funciona» y en el
            // panel no se ve una sola llamada suya.
            //
            // Si la clave no existe no se anota nada: serian palos de ciego
            // contra la API, y llenarian el historial de ruido.
            if ($llave) {
                $this->anotarRechazo($request, $llave, 'El secreto no coincide.');
            }

            return $this->error('Las credenciales no son válidas.', 401);
        }

        if (! $llave->activa) {
            $this->anotarRechazo($request, $llave, 'La llave está bloqueada.');

            return $this->error('Esta llave está bloqueada. Contacta con el proveedor.', 403);
        }

        if ($llave->vencida()) {
            $this->anotarRechazo($request, $llave, 'La llave venció el ' . $llave->expira_en->format('d/m/Y') . '.');

            return $this->error(
                'Esta llave venció el ' . $llave->expira_en->format('d/m/Y') . '.',
                403,
            );
        }

        // Sin plan no hay cuota que gastar, asi que no hay servicio. Pasa si se
        // borro el plan al que pertenecia.
        if (! $llave->plan) {
            $this->anotarRechazo($request, $llave, 'La llave no tiene plan asignado.');

            return $this->error('Esta llave no tiene plan asignado. Contacta con el proveedor.', 403);
        }

        // Para saber cuando se uso por ultima vez sin escribir en cada
        // peticion: una vez al dia basta para lo que sirve (ver llaves muertas).
        if ($llave->ultimo_uso_en === null || $llave->ultimo_uso_en->isBefore(now()->startOfDay())) {
            $llave->forceFill(['ultimo_uso_en' => now()])->saveQuietly();
        }

        $request->attributes->set('llave_consulta', $llave);

        return $next($request);
    }

    /**
     * Deja constancia de una llamada que no llego a entrar.
     *
     * Va a la misma tabla que las que si entran, marcada como fallida y con el
     * motivo: en el panel se ve al lado de las buenas, que es donde se mira
     * cuando alguien avisa de que su integracion dejo de funcionar.
     *
     * No gasta cuota: la cuota cuenta solo las que salieron bien.
     */
    private function anotarRechazo(Request $request, ConsultaLlave $llave, string $motivo): void
    {
        // El servicio sale de la propia URL: /api/consultas/{ruc|dni}/{numero},
        // porque la peticion no llega a la ruta y no hay parametros resueltos.
        $partes = explode('/', trim($request->path(), '/'));
        $tipo = in_array($partes[2] ?? '', ['ruc', 'dni'], true) ? $partes[2] : null;

        DB::table('consultas_consumo')->insert([
            'llave_id' => $llave->id,
            'company_id' => $llave->company_id,
            'api_id' => $tipo ? DB::table('apis')->where('slug', $tipo)->value('id') : null,
            'origen' => 'externo',
            'tipo' => $tipo ?? 'ruc',
            'numero' => mb_substr((string) ($partes[3] ?? ''), 0, 20),
            'fuente' => 'rechazada',
            'exito' => false,
            'ms' => 0,
            'motivo' => mb_substr($motivo, 0, 120),
            'created_at' => now(),
        ]);
    }

    private function error(string $mensaje, int $codigo): Response
    {
        return response()->json(['success' => false, 'message' => $mensaje], $codigo);
    }
}
