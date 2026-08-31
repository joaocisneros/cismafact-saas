<?php

namespace App\Http\Requests\Empresa;

use App\Support\NormativaSunat;

class StoreNotaDebitoRequest extends StoreNotaRequest
{
    // Catálogo 10 - Tipo de nota de débito
    private const TODOS_LOS_MOTIVOS = [
        '01' => 'Intereses por mora',
        '02' => 'Aumento en el valor',
        '03' => 'Otros conceptos',
        // Las penalidades van aparte y son inafectas al IGV, pero el 13 lo crea
        // la RS 000048-2026: hasta que entre en vigor SUNAT no lo reconoce.
        '13' => 'Penalidades',
    ];

    /** Motivos que SUNAT acepta hoy; el 13 aparece al entrar en vigor la RS 000048-2026. */
    public static function motivos(): array
    {
        return array_intersect_key(
            self::TODOS_LOS_MOTIVOS,
            array_flip(NormativaSunat::motivosNotaDebito())
        );
    }

    protected function serieRegex(): string
    {
        // ND suele usar series tipo FD01 / BD01
        return '/^[FB]D[A-Z0-9]{2}$/';
    }

    protected function motivosValidos(): array
    {
        return array_keys(self::motivos());
    }

    /** Igual que en la API: el motivo 13 no admite lineas gravadas. */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('cod_motivo') !== '13' || ! NormativaSunat::penalidadesInafectas()) {
                return;
            }

            foreach ((array) $this->input('detalles', []) as $i => $detalle) {
                $afectacion = (string) ($detalle['tip_afe_igv'] ?? $detalle['igv'] ?? '10');

                if (! str_starts_with($afectacion, '3')) {
                    $validator->errors()->add(
                        "detalles.{$i}.tip_afe_igv",
                        'Las penalidades son inafectas al IGV. Cambia la afectacion de esta linea '
                        . 'a inafecto, o SUNAT rechazara la nota.'
                    );
                }
            }
        });
    }
    /** Codigo SUNAT del comprobante: Nota de debito = 08. */
    protected function tipoDocumento(): string
    {
        return '08';
    }
}
