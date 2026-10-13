<?php

namespace App\Support;

final class SunatCdr
{
    /** SUNAT usa el codigo 0 para un CDR aceptado (tambien puede traer observaciones). */
    public static function isAccepted(?object $cdr): bool
    {
        return $cdr !== null
            && method_exists($cdr, 'getCode')
            && (string) $cdr->getCode() === '0';
    }

    /**
     * Informacion segura y uniforme para persistir la respuesta de SUNAT.
     *
     * @return array{id: mixed, code: mixed, description: mixed, notes: array}
     */
    public static function payload(?object $cdr): array
    {
        return [
            'id' => self::value($cdr, 'getId'),
            'code' => self::value($cdr, 'getCode'),
            'description' => self::value($cdr, 'getDescription'),
            'notes' => self::value($cdr, 'getNotes') ?? [],
        ];
    }

    private static function value(?object $cdr, string $method): mixed
    {
        return $cdr !== null && method_exists($cdr, $method)
            ? $cdr->{$method}()
            : null;
    }
}
