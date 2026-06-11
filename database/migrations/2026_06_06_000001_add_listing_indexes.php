<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->index('created_at', 'companies_created_at_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('created_at', 'users_created_at_idx');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'clients_company_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_company_created_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_created_at_idx');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('companies_created_at_idx');
        });
    }
};
