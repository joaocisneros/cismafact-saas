<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
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

        // En el panel de empresa este middleware solo debe actuar durante una
        // sesion de soporte: registrar cada accion de cada cliente llenaria la
        // tabla sin aportar nada.
        if (! $enSoporte && ! $request->user()?->hasRole('super_admin')) {
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
                // En soporte, el usuario autenticado es el de la empresa: se deja
                // constancia de quien estaba realmente detras de la accion.
                'description' => $enSoporte
                    ? '[SOPORTE: ' . Impersonation::nombreSuplantador() . '] ' . $request->route()?->getName()
                    : $request->route()?->getName(),
                'data' => $enSoporte
                    ? $request->except(self::SENSITIVE_FIELDS) + ['_impersonador_id' => Impersonation::idSuplantador()]
                    : $request->except(self::SENSITIVE_FIELDS),
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
