<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Company;
use App\Models\Correlative;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impide que una credencial empresarial opere recursos de otro tenant.
 *
 * AuthenticateApiKey deja la empresa autenticada en api_company. Este
 * middleware valida tambien los modelos resueltos mediante route binding,
 * porque sobrescribir company_id en el body no protege /companies/{company}
 * ni /branches/{branch}.
 */
class EnsureApiTenantOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->attributes->get('api_company');

        if (! $company instanceof Company) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No se pudo determinar la empresa de la credencial.',
            ], 401);
        }

        foreach (['company', 'company_id', 'branch', 'client', 'correlative'] as $parameter) {
            $value = $request->route($parameter);

            if ($value !== null && ! $this->belongsTo($value, $parameter, $company->id)) {
                // 404 evita confirmar que el recurso de otra empresa existe.
                return new JsonResponse([
                    'success' => false,
                    'message' => 'No se encontró el recurso.',
                ], 404);
            }
        }

        return $next($request);
    }

    private function belongsTo(mixed $value, string $parameter, int $companyId): bool
    {
        if ($value instanceof Company) {
            return (int) $value->id === $companyId;
        }

        if ($value instanceof Branch || $value instanceof Client || $value instanceof Correlative) {
            return (int) $value->company_id === $companyId;
        }

        if ($value instanceof Model && isset($value->company_id)) {
            return (int) $value->company_id === $companyId;
        }

        if ($parameter === 'company' || $parameter === 'company_id') {
            return (int) $value === $companyId;
        }

        if ($parameter === 'branch') {
            return Branch::whereKey($value)->where('company_id', $companyId)->exists();
        }

        if ($parameter === 'client') {
            return Client::whereKey($value)->where('company_id', $companyId)->exists();
        }

        if ($parameter === 'correlative') {
            return Correlative::whereKey($value)->where('company_id', $companyId)->exists();
        }

        return false;
    }
}
