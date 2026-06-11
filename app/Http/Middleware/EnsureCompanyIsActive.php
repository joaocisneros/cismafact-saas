<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SubscriptionStatusService;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->company?->subscription) {
            app(SubscriptionStatusService::class)->synchronize($user->company->subscription);
            $user->company->refresh();
        }

        if ($user && $user->company && !$user->company->activo) {
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'email' => 'La empresa asociada a tu cuenta está desactivada. Contacta al administrador.',
            ]);
        }

        return $next($request);
    }
}
