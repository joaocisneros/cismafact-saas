<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;

final class AuditContext
{
    /** @return array{actor_user_id:?int,actor_name:?string,actor_email:?string,target_email:?string,target_name:?string,service_context:string,environment_context:?string,event_type:?string} */
    public static function capture(
        ?string $route,
        array $input,
        mixed $subject,
        ?Company $company,
        ?User $actor,
    ): array {
        $route = (string) $route;

        return [
            'actor_user_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'actor_email' => $actor?->email,
            'target_email' => self::firstString($input, [
                'email', 'correo', 'titular_email',
                'cliente.email', 'client.email', 'customer.email',
                'usuario.email', 'user.email',
            ]),
            'target_name' => self::targetName($input, $subject),
            'service_context' => self::service($route),
            'environment_context' => self::environment($route, $input, $subject, $company),
            'event_type' => self::eventType($route, $input),
        ];
    }

    public static function service(string $route): string
    {
        if (str_contains($route, 'consultas') || str_contains($route, 'padron')) {
            return 'api_ruc_dni';
        }

        foreach (['factur', 'invoice', 'boleta', 'nota', 'guia', 'despatch', 'sunat', 'api-global', 'api-keys', 'tokens-prueba'] as $piece) {
            if (str_contains($route, $piece)) {
                return 'facturacion';
            }
        }

        return 'administracion';
    }

    private static function environment(string $route, array $input, mixed $subject, ?Company $company): ?string
    {
        $environment = $subject?->entorno ?? ($input['entorno'] ?? null);
        if (in_array($environment, ['sandbox', 'beta'], true)) {
            return 'sandbox';
        }
        if ($environment === 'produccion') {
            return 'produccion';
        }
        if (str_contains($route, 'sandbox') || str_contains($route, 'tokens-prueba')) {
            return 'sandbox';
        }

        return $company ? ($company->modo_produccion ? 'produccion' : 'beta') : null;
    }

    private static function eventType(string $route, array $input): ?string
    {
        $services = array_values(array_intersect((array) ($input['servicios'] ?? []), ['ruc', 'dni']));
        if ($services !== []) {
            return strtoupper(implode(' y ', $services));
        }

        $type = strtolower((string) ($input['tipo'] ?? $input['tipo_documento'] ?? $input['document_type'] ?? ''));
        if (in_array($type, ['ruc', 'dni'], true)) {
            return strtoupper($type);
        }

        $sunatType = match ($type) {
            '01' => 'Factura',
            '03' => 'Boleta',
            '07' => 'Nota de crédito',
            '08' => 'Nota de débito',
            '09', '31' => 'Guía de remisión',
            default => null,
        };
        if ($sunatType) {
            return $sunatType;
        }

        return match (true) {
            str_contains($route, 'factura'), str_contains($route, 'invoice') => 'Factura',
            str_contains($route, 'boleta') => 'Boleta',
            str_contains($route, 'credit'), str_contains($route, 'nota-credito') => 'Nota de crédito',
            str_contains($route, 'debit'), str_contains($route, 'nota-debito') => 'Nota de débito',
            str_contains($route, 'guia'), str_contains($route, 'despatch') => 'Guía de remisión',
            str_contains($route, 'llaves'), str_contains($route, 'api-key') => 'Credencial API',
            default => null,
        };
    }

    private static function firstString(array $input, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = data_get($input, $key);
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return mb_substr($value, 0, 150);
            }
        }

        return null;
    }

    private static function targetName(array $input, mixed $subject): ?string
    {
        foreach (['razon_social', 'name', 'nombre', 'titular', 'numero', 'serie'] as $attribute) {
            $value = is_object($subject) ? data_get($subject, $attribute) : null;
            if (is_scalar($value) && trim((string) $value) !== '') {
                return mb_substr(trim((string) $value), 0, 180);
            }
        }

        foreach ([
            'razon_social', 'name', 'nombre', 'titular', 'numero',
            'cliente.razon_social', 'cliente.nombre', 'cliente.name',
            'usuario.name', 'usuario.nombre', 'empresa.razon_social',
        ] as $key) {
            $value = data_get($input, $key);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return mb_substr(trim((string) $value), 0, 180);
            }
        }

        return null;
    }
}
