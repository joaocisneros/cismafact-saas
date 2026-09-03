<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * De qué día son los datos que trajo cada importación.
 *
 * El historial decía cuándo se importó, que no es lo mismo: el padrón es una
 * foto que SUNAT publica cada cierto tiempo, y entre esa foto y la
 * importación pueden pasar días. Sin este dato no había forma de saber si lo
 * que hay en la base es de la semana pasada o de hace dos meses.
 *
 * SUNAT lo dice en la cabecera Last-Modified del archivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('padron_importaciones', function (Blueprint $table) {
            $table->date('datos_de')->nullable()->after('filas');
        });
    }

    public function down(): void
    {
        Schema::table('padron_importaciones', function (Blueprint $table) {
            $table->dropColumn('datos_de');
        });
    }
};
