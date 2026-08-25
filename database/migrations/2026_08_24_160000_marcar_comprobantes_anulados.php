<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca de anulacion en el propio comprobante.
 *
 * Hasta ahora la Comunicacion de Baja se registraba en su tabla y SUNAT la
 * aceptaba, pero el comprobante seguia figurando como valido: aparecia en los
 * listados, en las estadisticas y en el Registro de Ventas que el cliente le
 * entrega a su contador. Es decir, se declaraba como ingreso una venta que ante
 * SUNAT ya no existe.
 *
 * 'anulado_en' se rellena SOLO cuando SUNAT acepta la baja (o el resumen, en el
 * caso de las boletas), nunca al enviarla: hasta la aceptacion el comprobante
 * sigue siendo valido.
 */
return new class extends Migration
{
    /** Tablas de comprobantes que pueden anularse. */
    private array $tablas = ['invoices', 'boletas', 'credit_notes', 'debit_notes'];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            if (! Schema::hasTable($tabla) || Schema::hasColumn($tabla, 'anulado_en')) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table) {
                $table->timestamp('anulado_en')->nullable()->after('estado_sunat');
                $table->unsignedBigInteger('anulado_por_documento_id')->nullable()->after('anulado_en');
                $table->string('anulado_motivo', 250)->nullable()->after('anulado_por_documento_id');

                $table->index('anulado_en');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, 'anulado_en')) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table) {
                $table->dropIndex(['anulado_en']);
                $table->dropColumn(['anulado_en', 'anulado_por_documento_id', 'anulado_motivo']);
            });
        }
    }
};
