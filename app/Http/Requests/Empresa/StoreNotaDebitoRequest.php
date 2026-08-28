<?php

namespace App\Http\Requests\Empresa;

class StoreNotaDebitoRequest extends StoreNotaRequest
{
    // Catálogo 10 - Tipo de nota de débito
    public const MOTIVOS = [
        '01' => 'Intereses por mora',
        '02' => 'Aumento en el valor',
        '03' => 'Otros conceptos',
        // Desde el 01/08/2026 las penalidades van aparte, y son inafectas al
        // IGV: si se mandan gravadas, SUNAT rechaza la nota.
        '13' => 'Penalidades',
    ];

    protected function serieRegex(): string
    {
        // ND suele usar series tipo FD01 / BD01
        return '/^[FB]D[A-Z0-9]{2}$/';
    }

    protected function motivosValidos(): array
    {
        return array_keys(self::MOTIVOS);
    }
    /** Codigo SUNAT del comprobante: Nota de debito = 08. */
    protected function tipoDocumento(): string
    {
        return '08';
    }
}
