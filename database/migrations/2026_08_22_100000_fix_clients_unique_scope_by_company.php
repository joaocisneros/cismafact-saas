<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El unique de clients era (tipo_documento, numero_documento) a nivel global:
 * venia de 2025_09_01_121823_create_clients_table, y cuando 2025_09_10_161649
 * agrego company_id el indice no se actualizo.
 *
 * Efecto: si una empresa registraba a un cliente con cierto DNI/RUC, ninguna
 * otra empresa podia volver a registrar a esa persona. La validacion del
 * controlador si filtra por company_id, asi que pasaba la validacion y luego
 * MySQL rechazaba el INSERT -> error 500.
 *
 * Aqui el unico pasa a ser (company_id, tipo_documento, numero_documento), que
 * es lo que la aplicacion siempre quiso decir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('clients_tipo_documento_numero_documento_unique');

            $table->unique(
                ['company_id', 'tipo_documento', 'numero_documento'],
                'clients_company_documento_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('clients_company_documento_unique');

            $table->unique(['tipo_documento', 'numero_documento']);
        });
    }
};
