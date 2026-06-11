<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['credit_notes', 'debit_notes'] as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'consulta_cpe_estado')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->string('consulta_cpe_estado', 2)->nullable()->after('estado_sunat')->index();
                $table->json('consulta_cpe_respuesta')->nullable()->after('consulta_cpe_estado');
                $table->timestamp('consulta_cpe_fecha')->nullable()->after('consulta_cpe_respuesta')->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['credit_notes', 'debit_notes'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'consulta_cpe_estado')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['consulta_cpe_estado']);
                $table->dropIndex(['consulta_cpe_fecha']);
                $table->dropColumn([
                    'consulta_cpe_estado',
                    'consulta_cpe_respuesta',
                    'consulta_cpe_fecha',
                ]);
            });
        }
    }
};
