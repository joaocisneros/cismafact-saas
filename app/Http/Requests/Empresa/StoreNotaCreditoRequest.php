<?php

namespace App\Http\Requests\Empresa;

class StoreNotaCreditoRequest extends StoreNotaRequest
{
    // Catálogo 09 - Tipo de nota de crédito
    public const MOTIVOS = [
        '01' => 'Anulación de la operación',
        '02' => 'Anulación por error en el RUC',
        '03' => 'Corrección por error en la descripción',
        '04' => 'Descuento global',
        '05' => 'Descuento por ítem',
        '06' => 'Devolución total',
        '07' => 'Devolución por ítem',
        '08' => 'Bonificación',
        '09' => 'Disminución en el valor',
        '10' => 'Otros conceptos',
    ];

    protected function serieRegex(): string
    {
        // NC suele usar series tipo FC01 / BC01
        return '/^[FB]C[A-Z0-9]{2}$/';
    }

    protected function motivosValidos(): array
    {
        return array_keys(self::MOTIVOS);
    }
    /** Codigo SUNAT del comprobante: Nota de credito = 07. */
    protected function tipoDocumento(): string
    {
        return '07';
    }
}
