<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['invoices', 'boletas', 'credit_notes', 'debit_notes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->index(['company_id', 'created_at'], "{$tableName}_company_created_idx");
                $table->index(['company_id', 'fecha_emision'], "{$tableName}_company_fecha_idx");
                $table->index(['company_id', 'estado_sunat'], "{$tableName}_company_estado_idx");
            });
        }
    }

    public function down(): void
    {
        foreach (['invoices', 'boletas', 'credit_notes', 'debit_notes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex("{$tableName}_company_created_idx");
                $table->dropIndex("{$tableName}_company_fecha_idx");
                $table->dropIndex("{$tableName}_company_estado_idx");
            });
        }
    }
};
