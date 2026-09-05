<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AsistenteWeb;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * El chat de la web.
 *
 * Abierto a internet a proposito —el que pregunta todavia no tiene cuenta— y
 * por eso con tope: sin el, uno solo puede dejarlo escribiendo toda la noche y
 * agotar la cuota gratuita del dia para todos los demas.
 *
 * La clave de OpenRouter no sale de aqui. Llamar al modelo desde el navegador
 * habria sido mas corto y habria dejado la clave a la vista de cualquiera que
 * abriera el inspector.
 */
class AsistenteController extends Controller
{
    public function responder(Request $request, AsistenteWeb $asistente): JsonResponse
    {
        if (! $asistente->disponible()) {
            return response()->json([
                'texto' => 'El asistente no está disponible. Escríbenos por WhatsApp.',
                'cerrado' => true,
            ], 503);
        }

        $datos = $request->validate([
            'pregunta' => ['required', 'string', 'min:2', 'max:' . config('asistente.limites.largo_maximo')],
            'historial' => ['sometimes', 'array', 'max:40'],
            'historial.*.rol' => ['required_with:historial', 'in:usuario,asistente'],
            'historial.*.texto' => ['required_with:historial', 'string', 'max:2000'],
        ], [
            'pregunta.max' => 'La pregunta es muy larga. Resúmela un poco.',
        ]);

        /*
         * Dos topes, que miden cosas distintas.
         *
         * El del minuto frena a quien pega la tecla; el de la sesion frena la
         * conversacion que no acaba nunca. El de la sesion se cuenta por
         * sesion y no por IP porque una oficina entera sale por la misma.
         */
        $porMinuto = 'asistente-minuto:' . $request->ip();

        if (RateLimiter::tooManyAttempts($porMinuto, config('asistente.limites.por_minuto'))) {
            return response()->json([
                'texto' => 'Vas muy rápido. Espera unos segundos y vuelve a preguntar.',
                'espera' => RateLimiter::availableIn($porMinuto),
            ], 429);
        }

        $gastados = (int) $request->session()->get('asistente_mensajes', 0);
        $tope = config('asistente.limites.mensajes_por_visitante');

        if ($gastados >= $tope) {
            return response()->json([
                'texto' => 'Hasta aquí llego yo. Para seguir, escríbenos por WhatsApp y te '
                    . 'atiende una persona.',
                'cerrado' => true,
            ], 429);
        }

        RateLimiter::hit($porMinuto, 60);
        $request->session()->put('asistente_mensajes', $gastados + 1);

        $respuesta = $asistente->responder($datos['pregunta'], $datos['historial'] ?? []);

        return response()->json([
            'texto' => $respuesta['texto'],
            'restantes' => max(0, $tope - ($gastados + 1)),
            'cerrado' => $respuesta['agotado'],
        ]);
    }
}
