<?php

namespace App\Services;

use App\Models\Boleta;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Invoice;
use Illuminate\Support\Carbon;

/**
 * Reporte de ventas para el contador.
 *
 * Reune en una sola tabla los cuatro tipos de comprobante de un periodo, con
 * las columnas que hacen falta para el Registro de Ventas: cliente, base
 * imponible, IGV y total.
 *
 * A diferencia del export anterior, no hay tope de filas: se recorre con cursor
 * para que una empresa con mucho volumen no agote la memoria.
 */
class AccountingReportService
{
    /** Modelo y etiqueta por tipo de comprobante, con su codigo SUNAT. */
    private const DOCUMENTOS = [
        ['modelo' => Invoice::class, 'etiqueta' => 'Factura', 'codigo' => '01'],
        ['modelo' => Boleta::class, 'etiqueta' => 'Boleta', 'codigo' => '03'],
        ['modelo' => CreditNote::class, 'etiqueta' => 'Nota de crédito', 'codigo' => '07'],
        ['modelo' => DebitNote::class, 'etiqueta' => 'Nota de débito', 'codigo' => '08'],
    ];

    /** Cabeceras del CSV, en el orden en que salen las filas. */
    public const CABECERAS = [
        'Fecha emisión',
        'Tipo',
        'Cód. SUNAT',
        'Serie',
        'Número',
        'Tipo doc. cliente',
        'Doc. cliente',
        'Cliente',
        'Gravadas',
        'Exoneradas',
        'Inafectas',
        'IGV',
        'Total',
        'Moneda',
        'Estado',
        'Modifica a',
    ];

    /** Nombres de los tipos de documento de identidad de SUNAT. */
    private const TIPOS_DOC_CLIENTE = [
        '0' => 'Sin documento',
        '1' => 'DNI',
        '4' => 'Carnet extranjería',
        '6' => 'RUC',
        '7' => 'Pasaporte',
        'A' => 'Céd. diplomática',
    ];

    /**
     * Filas del reporte, en orden de fecha. Devuelve un generador para no
     * cargar en memoria todos los comprobantes del periodo de golpe.
     *
     * @param  bool  $soloAceptados  Por defecto solo los que SUNAT acepto: un
     *                               comprobante rechazado no se declara.
     * @return \Generator<int, array<int, string>>
     */
    public function filas(int $companyId, string $desde, string $hasta, bool $soloAceptados = true): \Generator
    {
        $filas = [];

        foreach (self::DOCUMENTOS as $doc) {
            $consulta = $doc['modelo']::query()
                ->where('company_id', $companyId)
                ->whereBetween('fecha_emision', [$desde, $hasta])
                ->with('client:id,tipo_documento,numero_documento,razon_social')
                ->orderBy('fecha_emision')
                ->orderBy('serie')
                ->orderBy('correlativo');

            if ($soloAceptados) {
                $consulta->where('estado_sunat', 'ACEPTADO');
            }

            // Un comprobante con la baja aceptada por SUNAT ya no existe para
            // ella: incluirlo aqui haria que el contador declare una venta
            // anulada y pague impuestos por ella.
            $consulta->whereNull('anulado_en');

            foreach ($consulta->cursor() as $comprobante) {
                $filas[] = $this->fila($comprobante, $doc);
            }
        }

        // Orden final por fecha: los cuatro tipos van mezclados, como los espera
        // el contador en el Registro de Ventas.
        usort($filas, fn ($a, $b) => [$a[0], $a[3], $a[4]] <=> [$b[0], $b[3], $b[4]]);

        foreach ($filas as $fila) {
            yield $fila;
        }
    }

    /** Totales del periodo, para enseñarlos en pantalla antes de descargar. */
    public function totales(int $companyId, string $desde, string $hasta, bool $soloAceptados = true): array
    {
        $totales = ['documentos' => 0, 'gravadas' => 0.0, 'igv' => 0.0, 'total' => 0.0];

        foreach (self::DOCUMENTOS as $doc) {
            $consulta = $doc['modelo']::query()
                ->where('company_id', $companyId)
                ->whereBetween('fecha_emision', [$desde, $hasta]);

            if ($soloAceptados) {
                $consulta->where('estado_sunat', 'ACEPTADO');
            }

            $consulta->whereNull('anulado_en');

            $totales['documentos'] += (clone $consulta)->count();
            $totales['gravadas'] += (float) (clone $consulta)->sum('mto_oper_gravadas');
            $totales['igv'] += (float) (clone $consulta)->sum('mto_igv');
            $totales['total'] += (float) (clone $consulta)->sum('mto_imp_venta');
        }

        return $totales;
    }

    /** Una fila del reporte a partir de un comprobante. */
    private function fila($c, array $doc): array
    {
        $cliente = $c->client;

        // Las notas indican a que comprobante afectan; facturas y boletas no.
        $modifica = '';
        if (! empty($c->num_doc_afectado)) {
            $modifica = $c->num_doc_afectado;
        }

        return [
            $c->fecha_emision instanceof Carbon ? $c->fecha_emision->format('d/m/Y') : (string) $c->fecha_emision,
            $doc['etiqueta'],
            $doc['codigo'],
            (string) $c->serie,
            (string) $c->correlativo,
            self::TIPOS_DOC_CLIENTE[$cliente->tipo_documento ?? ''] ?? (string) ($cliente->tipo_documento ?? ''),
            (string) ($cliente->numero_documento ?? ''),
            (string) ($cliente->razon_social ?? ''),
            number_format((float) $c->mto_oper_gravadas, 2, '.', ''),
            number_format((float) ($c->mto_oper_exoneradas ?? 0), 2, '.', ''),
            number_format((float) ($c->mto_oper_inafectas ?? 0), 2, '.', ''),
            number_format((float) $c->mto_igv, 2, '.', ''),
            number_format((float) $c->mto_imp_venta, 2, '.', ''),
            (string) ($c->moneda ?? 'PEN'),
            (string) $c->estado_sunat,
            $modifica,
        ];
    }
}
