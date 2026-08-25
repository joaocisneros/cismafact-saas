<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege las rutas de /api/setup/*, que ejecutan migraciones, seeders y
 * exponen el estado interno del sistema.
 *
 * Requiere la cabecera X-Setup-Token con el valor de SETUP_TOKEN (.env).
 * Si SETUP_TOKEN no está definido, las rutas responden 404: quedan
 * desactivadas por defecto, que es lo correcto en producción.
 */
class EnsureSetupToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('app.setup_token');

        // Sin token configurado, el endpoint no existe.
        if ($expected === '') {
            abort(404);
        }

        $provided = (string) $request->header('X-Setup-Token', '');

        // hash_equals evita filtrar el token por diferencias de tiempo.
        if ($provided === '' || ! hash_equals($expected, $provided)) {
            Log::warning('Intento de acceso a /api/setup con token invalido', [
                'ip' => $request->ip(),
                'ruta' => $request->path(),
                'user_agent' => $request->userAgent(),
            ]);

            abort(404);
        }

        return $next($request);
    }
}
