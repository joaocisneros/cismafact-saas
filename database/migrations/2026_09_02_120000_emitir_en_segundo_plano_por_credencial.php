<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Si esta credencial espera a SUNAT o responde al momento.
 *
 * Emitir esperando deja el proceso ocupado lo que tarde SUNAT —de dos segundos
 * a media hora— y son los mismos procesos que sirven el panel. En segundo plano
 * se guarda, se responde en milisegundos y el envio va por su cuenta.
 *
 * Apagado por defecto: quien ya integro lee el CDR en la respuesta de emitir, y
 * encenderselo de golpe le dejaria de llegar. Las credenciales nuevas se crean
 * con el encendido, que es lo que conviene a quien empieza hoy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $tabla) {
            $tabla->boolean('emitir_async')->default(false)->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $tabla) {
            $tabla->dropColumn('emitir_async');
        });
    }
};
