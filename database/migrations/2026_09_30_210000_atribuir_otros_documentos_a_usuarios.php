<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['credit_notes', 'debit_notes', 'dispatch_guides'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('created_by_user_id')->nullable()->after('api_key_id')
                    ->constrained('users')->nullOnDelete();
                $table->index(['company_id', 'created_by_user_id', 'fecha_emision'], 'otros_docs_empresa_usuario_fecha_idx');
            });

            DB::statement("UPDATE {$tabla} d JOIN (
                SELECT company_id, name, MIN(id) user_id
                FROM users
                GROUP BY company_id, name
                HAVING COUNT(*) = 1
            ) u ON u.company_id = d.company_id AND u.name = d.usuario_creacion
            SET d.created_by_user_id = u.user_id
            WHERE d.created_by_user_id IS NULL");
        }
    }

    public function down(): void
    {
        foreach (['credit_notes', 'debit_notes', 'dispatch_guides'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropIndex('otros_docs_empresa_usuario_fecha_idx');
                $table->dropConstrainedForeignId('created_by_user_id');
            });
        }
    }
};
