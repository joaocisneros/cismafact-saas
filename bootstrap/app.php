<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'company.active' => \App\Http\Middleware\EnsureCompanyIsActive::class,
            'api.key' => \App\Http\Middleware\AuthenticateApiKey::class,
            'audit.admin' => \App\Http\Middleware\AuditAdminActions::class,
            'setup.token' => \App\Http\Middleware\EnsureSetupToken::class,
        ]);

        // Cabeceras de seguridad en todas las respuestas web.
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // "Página expirada" (419) es casi siempre una pantalla que el navegador
        // tenía abierta desde antes: al entrar o salir del modo soporte cambia
        // el usuario de la sesión y con el el token CSRF, y el boton de esa
        // pantalla vieja ya no sirve. En vez del error seco, se devuelve al
        // usuario a la misma pagina recien generada para que reintente.
        // Laravel convierte TokenMismatchException en HttpException(419) antes
        // de llegar aqui, por eso se filtra por codigo y no por la clase.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            return redirect()
                ->to(url()->previous())
                ->with('error', 'La página había caducado (tu sesión cambió). Ya está actualizada: vuelve a pulsar el botón.');
        });
    })->create();
