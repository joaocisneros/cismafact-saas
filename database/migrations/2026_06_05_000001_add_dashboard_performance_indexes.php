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
                $table->index(['created_at'], "{$tableName}_created_idx");
                $table->index(['estado_sunat', 'created_at'], "{$tableName}_estado_created_idx");
            });
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->index(['activo', 'created_at'], 'companies_activo_created_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['active', 'created_at'], 'users_active_created_idx');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['company_id', 'status', 'created_at'], 'tickets_company_status_created_idx');
        });
    }

    public function down(): void
    {
        foreach (['invoices', 'boletas', 'credit_notes', 'debit_notes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex("{$tableName}_created_idx");
                $table->dropIndex("{$tableName}_estado_created_idx");
            });
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('companies_activo_created_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_active_created_idx');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_company_status_created_idx');
        });
    }
};
