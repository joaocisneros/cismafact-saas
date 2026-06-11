<?php

namespace App\Http\Controllers\Traits;

use App\Services\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

trait EnforcesPlanLimits
{
    /**
     * Verifica el límite mensual de documentos del plan de la empresa.
     *
     * Devuelve una respuesta de redirección con error si la empresa ya alcanzó
     * el tope de su plan (igual que hace la API), o null si todavía puede emitir.
     * El cupo se reinicia solo cada mes (ver PlanLimitService::documentsUsedThisMonth).
     */
    protected function documentLimitReachedResponse(): ?RedirectResponse
    {
        $company = Auth::user()?->company;
        $plan = $company?->plan;

        // Las empresas SANDBOX/DEMO no tienen tope (emiten ilimitado en beta).
        if (! $company || ! $plan || $company->es_demo) {
            return null;
        }

        $limits = app(PlanLimitService::class);

        if ($limits->limitReached(
            (int) $plan->monthly_document_limit,
            $limits->documentsUsedThisMonth($company->id)
        )) {
            return back()->withInput()->with('error', sprintf(
                'Alcanzaste el límite de %d documentos mensuales de tu plan %s. Sube de plan para emitir más.',
                (int) $plan->monthly_document_limit,
                $plan->name
            ));
        }

        return null;
    }
}
