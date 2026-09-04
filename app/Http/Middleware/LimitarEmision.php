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

    /**
     * Emisiones por minuto en todo el sistema.
     *
     * El limite por credencial no basta: son veinte por minuto CADA UNO, asi
     * que con ocho clientes a tope el servidor se llena igual y el panel deja
     * de responder para todos.
     *
     * Sesenta por minuto, con los noventa segundos que tarda SUNAT de media,
     * son unos noventa procesos ocupados a la vez. De los doscientos
     * cincuenta que hay, dejan mas de la mitad libres para el panel pase lo
     * que pase.
     */
    private const GLOBAL_POR_MINUTO = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $llave = $request->attributes->get('api_key');

        // Sin credencial identificada no se limita aqui: de eso ya se ocupa
        // api.key, que rechaza antes de llegar.
        if (! $llave) {
            return $next($request);
        }

        // Consultar no es emitir. Listar comprobantes, ver uno o descargar su
        // PDF no ocupa a SUNAT ni deja un proceso esperando, asi que no tiene
        // por que gastar cupo. Entraban aqui porque el middleware cubre el
        // grupo de rutas entero, no porque debieran.
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        // El tope general va primero: si el sistema esta saturado da igual
        // quien pregunte, y asi el que llega no gasta su cupo propio en una
        // peticion que no se va a atender.
        if (RateLimiter::tooManyAttempts('emision:sistema', self::GLOBAL_POR_MINUTO)) {
            $faltan = RateLimiter::availableIn('emision:sistema');

            return response()->json([
                'success' => false,
                'message' => 'El sistema está atendiendo muchas emisiones ahora mismo. '
                    . "Vuelve a intentarlo en {$faltan} segundos.",
                'reintentar_en' => $faltan,
            ], 429)->header('Retry-After', $faltan);
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
        RateLimiter::hit('emision:sistema', 60);

        $respuesta = $next($request);

        // Lo que queda, para que quien integra pueda repartir su carga sin
        // tener que chocarse con el limite para descubrirlo.
        //
        // Por headers->set y no por ->header(): lo segundo solo lo tienen las
        // respuestas de Laravel, y no todas lo son. Una descarga se devuelve
        // en streaming, que es de Symfony y no conoce ese metodo: pedir el PDF
        // de un comprobante moria aqui con un 500 despues de haberlo emitido
        // bien.
        $respuesta->headers->set('X-RateLimit-Limit', (string) self::POR_MINUTO);
        $respuesta->headers->set('X-RateLimit-Remaining', (string) RateLimiter::remaining($cubo, self::POR_MINUTO));

        return $respuesta;
    }
}
