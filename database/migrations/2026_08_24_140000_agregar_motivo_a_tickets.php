<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Motivo del ticket.
 *
 * El buzon era generico: llegaba igual "no me emite la factura" que "quiero
 * renovar mi plan", y hay que abrirlos uno a uno para saber cual es cual. Son
 * cosas distintas: una la resuelve soporte y la otra termina en Suscripciones,
 * y la segunda es la que no conviene dejar esperando.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('motivo', ['soporte', 'renovacion', 'consulta'])
                ->default('soporte')
                ->after('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('motivo');
        });
    }
};
