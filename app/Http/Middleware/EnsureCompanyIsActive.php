<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SubscriptionStatusService;
use App\Support\Impersonation;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $enSoporte = Impersonation::activa();

        // synchronize() no solo lee: marca la suscripcion como vencida y suspende
        // la empresa. Entrar a mirar una cuenta como soporte no puede cambiarle
        // el estado al cliente, asi que durante la suplantacion no se ejecuta.
        // El cron (synchronizeDueSubscriptions) sigue haciendo ese trabajo.
        if (! $enSoporte && $user?->company?->subscription) {
            app(SubscriptionStatusService::class)->synchronize($user->company->subscription);
            $user->company->refresh();
        }

        if ($user && $user->company && !$user->company->activo) {
            // En sesión de soporte no se cierra la sesión: revisar una empresa
            // suspendida es justamente uno de los motivos para entrar.
            if ($enSoporte) {
                return $next($request);
            }

            Auth::logout();

            return redirect()->route('login')->withErrors([
                'email' => $this->motivoDelCorte($user->company),
            ]);
        }

        return $next($request);
    }

    /**
     * Explica por que la cuenta esta cerrada. Un "esta desactivada" a secas deja
     * al cliente sin saber si es un fallo, una deuda o algo suyo; el motivo mas
     * frecuente es que se le vencio el plan, y eso lo resuelve el mismo.
     */
    private function motivoDelCorte(\App\Models\Company $company): string
    {
        $suscripcion = $company->subscription;

        if (! $company->suspendida_manualmente && $suscripcion?->ends_at && $suscripcion->ends_at->isPast()) {
            return 'Tu plan venció el ' . $suscripcion->ends_at->format('d/m/Y')
                . '. Comunícate con nosotros para reactivar tu cuenta y volver a emitir comprobantes.';
        }

        return 'La empresa asociada a tu cuenta está desactivada. Contacta al administrador.';
    }
}
