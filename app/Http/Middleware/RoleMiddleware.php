<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->active) {
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta está desactivada.',
            ]);
        }

        if (count($roles) > 0 && !$user->hasAnyRole($roles)) {
            return redirect()->route('dashboard')->with('error', 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
