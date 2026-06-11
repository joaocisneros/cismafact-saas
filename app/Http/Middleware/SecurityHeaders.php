<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agrega cabeceras de seguridad HTTP a todas las respuestas web.
 * Protege contra clickjacking, sniffing de MIME y fuga de referer.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Evita que el sitio se embeba en iframes de otros dominios (clickjacking).
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // El navegador no debe "adivinar" el tipo de contenido.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // No filtrar la URL completa como referer hacia otros sitios.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Desactiva APIs sensibles del navegador que la app no usa.
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Fuerza HTTPS durante 1 año (solo cuando ya se sirve por HTTPS).
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // No revelar tecnología del servidor. PHP añade X-Powered-By a nivel de
        // SAPI, por eso se quita con header_remove (además de la bolsa de la
        // respuesta). En producción conviene también expose_php=Off en php.ini.
        $response->headers->remove('X-Powered-By');
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}
