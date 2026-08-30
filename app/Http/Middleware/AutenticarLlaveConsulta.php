<?php

namespace App\Http\Middleware;

use App\Models\ConsultaLlave;
use Closure;
use Illuminate\Http\Request;
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
        if (! $llave || ! hash_equals((string) $llave->secreto, $secreto)) {
            return $this->error('Las credenciales no son válidas.', 401);
        }

        if (! $llave->activa) {
            return $this->error('Esta llave está bloqueada. Contacta con el proveedor.', 403);
        }

        if ($llave->vencida()) {
            return $this->error(
                'Esta llave venció el ' . $llave->expira_en->format('d/m/Y') . '.',
                403,
            );
        }

        // Sin plan no hay cuota que gastar, asi que no hay servicio. Pasa si se
        // borro el plan al que pertenecia.
        if (! $llave->plan) {
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

    private function error(string $mensaje, int $codigo): Response
    {
        return response()->json(['success' => false, 'message' => $mensaje], $codigo);
    }
}
