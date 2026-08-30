<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada consulta que hace un cliente.
 *
 * Aparte de consultas_documento, que guarda EL DATO para no volver a pedirlo.
 * Esta guarda EL HECHO de haber consultado, que es lo que se cobra y lo que
 * hay que contar. Dos clientes preguntando por el mismo RUC gastan dos
 * consultas aunque el dato salga de la misma fila.
 *
 * Se anota tambien de donde salio: asi se ve cuanto se esta yendo al proveedor
 * de verdad y cuanto se resuelve ya en casa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultas_consumo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->char('tipo', 3);
            $table->string('numero', 11);
            $table->string('fuente', 20);
            $table->timestamp('created_at')->nullable();

            // Contar lo del mes de una empresa es la consulta que mas se hace.
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas_consumo');
    }
};
