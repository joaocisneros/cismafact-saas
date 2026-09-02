<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fuera la copia legible del secret de las API Keys.
 *
 * Junto al hash se guardaba el secret cifrado con la APP_KEY, para poder
 * enseñarlo cuando el cliente lo pidiera. Comodo, y a cambio el secret de cada
 * cliente quedaba al alcance de quien alcanzara la base y el .env, que estan
 * en el mismo servidor. Un hash no se revierte ni teniendo las dos cosas.
 *
 * Se entrega al crearlo y al regenerarlo, que es cuando hace falta. Quien lo
 * pierda pide otro: la key no cambia y su integracion sigue igual.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('api_keys', 'plain_secret')) {
            Schema::table('api_keys', function (Blueprint $tabla) {
                $tabla->dropColumn('plain_secret');
            });
        }
    }

    public function down(): void
    {
        // Vuelve la columna, vacia: lo que habia dentro no se puede recuperar,
        // y es justo lo que se buscaba.
        if (! Schema::hasColumn('api_keys', 'plain_secret')) {
            Schema::table('api_keys', function (Blueprint $tabla) {
                $tabla->text('plain_secret')->nullable()->after('secret');
            });
        }
    }
};
