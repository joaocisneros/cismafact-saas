<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cuantas emisiones por minuto admite cada credencial.
 *
 * Emitir no es como consultar: la peticion se queda esperando la respuesta de
 * SUNAT —entre nada y media hora, segun el dia— y mientras tanto ocupa uno de
 * los procesos del servidor. Los procesos son los mismos que sirven el panel,
 * asi que un cliente que suelte su carga del dia de golpe deja sin servicio a
 * todos los demas sin pretenderlo.
 *
 * El limite es por credencial y no global: quien se pasa se frena a si mismo.
 * Y se cuenta por minuto, que es como llegan los lotes.
 */
class LimitarEmision
{
    /** Emisiones por minuto y credencial. */
    private const POR_MINUTO = 20;

    public function handle(Request $request, Closure $next): Response
    {
        $llave = $request->attributes->get('api_key');

        // Sin credencial identificada no se limita aqui: de eso ya se ocupa
        // api.key, que rechaza antes de llegar.
        if (! $llave) {
            return $next($request);
        }

        $cubo = 'emision:' . $llave->id;

        if (RateLimiter::tooManyAttempts($cubo, self::POR_MINUTO)) {
            $faltan = RateLimiter::availableIn($cubo);

            return response()->json([
                'success' => false,
                'message' => "Vas demasiado rápido: el máximo es de " . self::POR_MINUTO
                    . " comprobantes por minuto. Vuelve a intentarlo en {$faltan} segundos.",
                'reintentar_en' => $faltan,
            ], 429)->header('Retry-After', $faltan);
        }

        RateLimiter::hit($cubo, 60);

        $respuesta = $next($request);

        // Lo que queda, para que quien integra pueda repartir su carga sin
        // tener que chocarse con el limite para descubrirlo.
        return $respuesta
            ->header('X-RateLimit-Limit', self::POR_MINUTO)
            ->header('X-RateLimit-Remaining', RateLimiter::remaining($cubo, self::POR_MINUTO));
    }
}
