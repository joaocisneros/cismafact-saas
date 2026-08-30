<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consulta propia de RUC y DNI.
 *
 * Tres tablas con papeles distintos:
 *
 *   padron_ruc            copia local del padron reducido de SUNAT. Responde
 *                         primero, gratis y sin salir a internet. Ocupa 2-3 GB
 *                         con los ~11 millones de RUC, asi que puede estar
 *                         vacia: el servicio funciona igual sin ella.
 *
 *   padron_importaciones  historial de descargas. La importacion tarda y corre
 *                         en segundo plano, asi que hace falta saber en que va.
 *
 *   consultas_documento   lo que ya se pregunto alguna vez. En la practica se
 *                         convierte en el padron que de verdad importa: los RUC
 *                         a los que los clientes facturan, que son cientos, no
 *                         millones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('padron_ruc', function (Blueprint $table) {
            $table->char('ruc', 11)->primary();
            $table->string('nombre');
            $table->string('estado', 30)->default('');
            $table->string('condicion', 30)->default('');
            $table->char('ubigeo', 6)->nullable();
            $table->string('direccion')->default('');

            // Buscar por nombre: solo los primeros caracteres, que el indice
            // completo sobre 11 millones de filas pesaria mas que la tabla.
            $table->index([DB::raw('nombre(40)')], 'idx_padron_nombre');
        });

        Schema::create('padron_importaciones', function (Blueprint $table) {
            $table->id();
            $table->enum('estado', ['descargando', 'importando', 'completada', 'fallida']);
            $table->unsignedBigInteger('filas')->default(0);
            $table->unsignedBigInteger('bytes_descargados')->default(0);
            $table->string('mensaje', 500)->default('');
            $table->timestamp('iniciada_en')->nullable();
            $table->timestamp('terminada_en')->nullable();
            $table->timestamps();
        });

        Schema::create('consultas_documento', function (Blueprint $table) {
            $table->id();
            $table->char('tipo', 3);            // ruc | dni
            $table->string('numero', 11);
            $table->json('datos');
            $table->string('fuente', 30);       // padron | proveedor
            $table->timestamps();

            $table->unique(['tipo', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas_documento');
        Schema::dropIfExists('padron_importaciones');
        Schema::dropIfExists('padron_ruc');
    }
};
