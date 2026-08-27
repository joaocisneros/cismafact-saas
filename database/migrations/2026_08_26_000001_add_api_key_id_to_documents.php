<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda en cada comprobante con qué token de la API se emitió.
 *
 * Los tokens de prueba cuelgan todos de la misma empresa demo, y tiene que ser
 * así: el certificado de pruebas solo firma con el RUC 20123456789. Sin esta
 * columna, cada programador al que se le entrega un token ve los comprobantes
 * de todos los demás y no distingue los suyos.
 *
 * Admite nulos a propósito: lo emitido desde el panel web no viene de ningún
 * token, y lo ya emitido antes de esta migración tampoco.
 */
return new class extends Migration
{
    /** Las cinco tablas de comprobantes que se emiten por API. */
    private array $tablas = [
        'invoices',
        'boletas',
        'credit_notes',
        'debit_notes',
        'dispatch_guides',
    ];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            if (! Schema::hasTable($tabla) || Schema::hasColumn($tabla, 'api_key_id')) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('api_key_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('api_keys')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, 'api_key_id')) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table) {
                $table->dropConstrainedForeignId('api_key_id');
            });
        }
    }
};
