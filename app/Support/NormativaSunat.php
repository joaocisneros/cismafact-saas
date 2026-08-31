<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Reglas de la RS 000048-2026/SUNAT que aun NO estan vigentes.
 *
 * La resolucion iba a regir desde el 01/08/2026, pero la RS 000143-2026/SUNAT
 * (publicada el 29/07/2026) aplazo su entrada en vigor al 01/01/2027, y la
 * designacion de emisores electronicos al 01/04/2027.
 *
 * Hasta esa fecha SUNAT sigue aceptando el formato anterior, asi que adelantar
 * las validaciones no protege a nadie: rechaza comprobantes que SUNAT admite.
 * Por eso las reglas se activan solas al llegar la fecha, sin tocar codigo.
 */
class NormativaSunat
{
    /** Entrada en vigor de la RS 000048-2026 segun la RS 000143-2026. */
    public const VIGENCIA_RS_048 = '2027-01-01';

    /** Designacion de emisores de DAE/DAEE (documentos de atribucion). */
    public const VIGENCIA_DAE = '2027-04-01';

    public static function rs048Vigente(): bool
    {
        return Carbon::now()->startOfDay()->gte(Carbon::parse(self::VIGENCIA_RS_048));
    }

    public static function daeVigente(): bool
    {
        return Carbon::now()->startOfDay()->gte(Carbon::parse(self::VIGENCIA_DAE));
    }

    /**
     * Codigo de producto SUNAT (UNSPSC).
     *
     * Desde el 01/01/2027 son 8 digitos numericos o SUNAT rechaza el
     * comprobante (ERR-3496 / ERR-3506). Antes de esa fecha, como mucho lo
     * observa, asi que no bloqueamos al emisor por un catalogo sin migrar.
     */
    public static function reglaCodigoProducto(): string
    {
        return self::rs048Vigente() ? 'nullable|digits:8' : 'nullable|string|max:50';
    }

    /**
     * Catalogo 10 - motivos de nota de debito.
     *
     * El 13 (penalidades) lo crea la RS 000048-2026, asi que antes de su
     * vigencia no existe para SUNAT y ofrecerlo solo consigue un rechazo.
     */
    public static function motivosNotaDebito(): array
    {
        $motivos = ['01', '02', '03', '10', '11'];

        if (self::rs048Vigente()) {
            $motivos[] = '13';
        }

        return $motivos;
    }

    /** El motivo 13 exige lineas inafectas al IGV (ERR-3507), desde 2027. */
    public static function penalidadesInafectas(): bool
    {
        return self::rs048Vigente();
    }
}
