<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\ApiUsage;
use App\Models\User;
use App\Services\PlanLimitService;
use App\Services\SubscriptionStatusService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        $apiKeyValue = $request->header('X-Api-Key') ?? $request->header('X-API-KEY');
        $apiSecretValue = $request->header('X-Api-Secret') ?? $request->header('X-API-SECRET');

        if (! $apiKeyValue || ! $apiSecretValue) {
            return response()->json([
                'success' => false,
                'message' => 'Debes enviar X-Api-Key y X-Api-Secret.',
            ], 401);
        }

        $apiKey = ApiKey::query()
            ->with(['company.branches' => fn ($query) => $query->where('activo', true)->oldest('id')])
            ->where('key', $apiKeyValue)
            ->where('active', true)
            ->first();

        if (! $apiKey || ! Hash::check($apiSecretValue, $apiKey->secret)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales API invalidas.',
            ], 401);
        }

        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'El token API ha caducado. Solicita uno nuevo o que extiendan su vigencia.',
                'code' => 'API_KEY_EXPIRED',
            ], 401);
        }

        if (! $apiKey->company || ! $apiKey->company->activo) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa inactiva o suspendida.',
            ], 403);
        }

        $company = $apiKey->company->loadMissing(['plan', 'subscription']);

        if ($company->subscription) {
            app(SubscriptionStatusService::class)->synchronize($company->subscription);
            $company->refresh();
        }

        if (! $company->activo) {
            return response()->json([
                'success' => false,
                'message' => 'La suscripción de la empresa está suspendida o vencida.',
            ], 403);
        }

        $plan = $company->plan;
        $limits = app(PlanLimitService::class);

        // Las empresas SANDBOX/DEMO no tienen tope (prueban ilimitado en beta).
        $sinTope = (bool) $company->es_demo;

        if (! $sinTope && $plan && $limits->limitReached(
            $plan->api_request_limit,
            $limits->apiRequestsUsedThisMonth($company->id)
        )) {
            return response()->json([
                'success' => false,
                'message' => 'La empresa alcanzó el límite mensual de solicitudes API de su plan.',
                'code' => 'API_PLAN_LIMIT_REACHED',
            ], 429);
        }

        if (! $sinTope && $plan && $this->isDocumentCreationRequest($request) && $limits->limitReached(
            $plan->monthly_document_limit,
            $limits->documentsUsedThisMonth($company->id)
        )) {
            return response()->json([
                'success' => false,
                'message' => 'La empresa alcanzó el límite mensual de documentos de su plan.',
                'code' => 'DOCUMENT_PLAN_LIMIT_REACHED',
            ], 422);
        }

        $user = User::query()
            ->where('company_id', $apiKey->company_id)
            ->where('active', true)
            ->oldest('id')
            ->first();

        if ($user) {
            Auth::setUser($user);
            $request->setUserResolver(fn () => $user);
        }

        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('api_company', $apiKey->company);

        $request->merge([
            'company_id' => $request->input('company_id', $apiKey->company_id),
            'branch_id' => $request->input('branch_id', $apiKey->company->branches->first()?->id),
        ]);

        $apiKey->forceFill(['last_used_at' => now()])->save();

        $startedAt = microtime(true);
        $response = $next($request);

        ApiUsage::create([
            'company_id' => $company->id,
            'api_key_id' => $apiKey->id,
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'request_body' => null,
            'response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'created_at' => now(),
        ]);

        return $response;
    }

    private function isDocumentCreationRequest(Request $request): bool
    {
        if (! $request->isMethod('post')) {
            return false;
        }

        return (bool) preg_match(
            '#^api/(?:boletas|facturas|notas-credito|notas-debito|guias-remision|v1/(?:invoices|boletas|credit-notes|debit-notes|dispatch-guides))/?$#',
            $request->path()
        );
    }
}
