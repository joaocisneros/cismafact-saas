<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ContactoWeb;
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

    /**
     * Guarda a quien pidio que le escriban.
     *
     * Se le pide el numero y no el correo porque aqui se cierra por WhatsApp:
     * pedir un correo para luego escribirle igual al movil es un paso de mas
     * que hace que la mitad no lo rellene.
     */
    public function contacto(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:2', 'max:120'],
            // Nueve digitos con prefijo o sin el, y se admiten espacios y
            // guiones porque la gente los escribe.
            'telefono' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[\d\s\-\+\(\)]+$/'],
            'mensaje' => ['nullable', 'string', 'max:500'],
            'interes' => ['nullable', 'string', 'in:facturacion,consultas'],
        ], [
            'telefono.regex' => 'El número solo lleva dígitos.',
            'nombre.min' => 'Escribe tu nombre.',
        ]);

        $porIp = 'contacto-web:' . $request->ip();

        // Tres al dia por conexion. Un formulario abierto a internet lo
        // encuentran los robots, y con la bandeja llena de basura se acaba sin
        // mirar la que si era buena.
        if (RateLimiter::tooManyAttempts($porIp, 3)) {
            return response()->json([
                'texto' => 'Ya recibimos tus datos. Te escribimos en breve.',
                'guardado' => true,
            ]);
        }

        RateLimiter::hit($porIp, 86400);

        $contacto = ContactoWeb::create($datos + ['ip' => $request->ip()]);

        return response()->json([
            'texto' => "Gracias, {$contacto->nombre}. Te escribimos por WhatsApp lo antes posible.",
            'guardado' => true,
        ]);
    }
}