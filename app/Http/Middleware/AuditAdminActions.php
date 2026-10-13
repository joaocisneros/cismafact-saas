<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use App\Support\AuditContext;
use App\Support\Impersonation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditAdminActions
{
    private const SENSITIVE_FIELDS = [
        '_token',
        '_method',
        'password',
        'password_confirmation',
        'new_password',
        'new_password_confirmation',
        'smtp_password',
        'api_secret',
        'secret',
        'plain_secret',
        'clave_sol',
        'certificado_password',
        'gre_clave_sol',
        'gre_client_secret_beta',
        'gre_client_secret_produccion',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        // Entrar y salir de soporte ya se registran con su propio detalle en
        // ImpersonationController; no hace falta el registro generico.
        if (in_array($request->route()?->getName(), ['super-admin.companies.impersonate', 'impersonate.stop'], true)) {
            return $response;
        }

        $enSoporte = Impersonation::activa();

        try {
            $parameters = $request->route()?->parameters() ?? [];
            $subject = collect($parameters)->first(fn ($value) => is_object($value) && isset($value->id));
            $companyId = $subject instanceof Company
                ? $subject->id
                : ($subject?->company_id ?? $request->user()?->company_id);
            $company = $subject instanceof Company
                ? $subject
                : ($companyId ? Company::find($companyId) : null);
            $actor = $enSoporte
                ? User::find(Impersonation::idSuplantador())
                : $request->user();
            $safeInput = $this->sanitizeInput($request->except(self::SENSITIVE_FIELDS));
            $context = AuditContext::capture(
                $request->route()?->getName(),
                $safeInput,
                $subject,
                $company,
                $actor,
            );

            AuditLog::create([
                'user_id' => $request->user()?->id,
                ...$context,
                'company_id' => $companyId,
                'action' => match ($request->method()) {
                    'POST' => 'create_or_action',
                    'PUT', 'PATCH' => 'update',
                    'DELETE' => 'delete',
                },
                'method' => $request->method(),
                'route_name' => $request->route()?->getName(),
                'path' => $request->path(),
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->id,
                // En soporte, el usuario autenticado es el de la empresa: se deja
                // constancia de quien estaba realmente detras de la accion.
                'description' => $enSoporte
                    ? '[SOPORTE: ' . Impersonation::nombreSuplantador() . '] ' . $request->route()?->getName()
                    : $request->route()?->getName(),
                'data' => $enSoporte
                    ? $safeInput + ['_impersonador_id' => Impersonation::idSuplantador()]
                    : $safeInput,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'response_status' => $response->getStatusCode(),
            ]);
        } catch (\Throwable) {
            // La auditoria nunca debe interrumpir una operacion administrativa.
        }

        return $response;
    }

    /** Elimina secretos aunque lleguen dentro de estructuras anidadas. */
    private function sanitizeInput(array $input): array
    {
        $sensitive = array_map('strtolower', self::SENSITIVE_FIELDS);

        foreach ($input as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                unset($input[$key]);
                continue;
            }

            if (is_array($value)) {
                $input[$key] = $this->sanitizeInput($value);
            }
        }

        return $input;
    }
}
