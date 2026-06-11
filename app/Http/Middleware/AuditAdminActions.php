<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
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

        try {
            $parameters = $request->route()?->parameters() ?? [];
            $subject = collect($parameters)->first(fn ($value) => is_object($value) && isset($value->id));

            AuditLog::create([
                'user_id' => $request->user()?->id,
                'company_id' => $subject?->company_id ?? $request->user()?->company_id,
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
                'description' => $request->route()?->getName(),
                'data' => $request->except(self::SENSITIVE_FIELDS),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'response_status' => $response->getStatusCode(),
            ]);
        } catch (\Throwable) {
            // La auditoria nunca debe interrumpir una operacion administrativa.
        }

        return $response;
    }
}
