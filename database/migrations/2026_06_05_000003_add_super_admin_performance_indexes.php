<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['invoices', 'boletas', 'credit_notes', 'debit_notes', 'dispatch_guides'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->index('numero_completo', "{$tableName}_numero_completo_idx");
            });
        }

        Schema::table('dispatch_guides', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'dispatch_guides_company_created_idx');
            $table->index(['company_id', 'fecha_emision'], 'dispatch_guides_company_fecha_idx');
            $table->index(['company_id', 'estado_sunat'], 'dispatch_guides_company_estado_idx');
            $table->index(['estado_sunat', 'created_at'], 'dispatch_guides_estado_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_guides', function (Blueprint $table) {
            $table->dropIndex('dispatch_guides_estado_created_idx');
            $table->dropIndex('dispatch_guides_company_estado_idx');
            $table->dropIndex('dispatch_guides_company_fecha_idx');
            $table->dropIndex('dispatch_guides_company_created_idx');
        });

        foreach (['invoices', 'boletas', 'credit_notes', 'debit_notes', 'dispatch_guides'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex("{$tableName}_numero_completo_idx");
            });
        }
    }
};
