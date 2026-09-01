<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una llave de prueba puede devolver datos reales.
 *
 * Hasta ahora sandbox era siempre de mentira, y eso sirve para que un
 * programador integre sin gastar cuota, pero no para enseñarle a un cliente que
 * el servicio funciona: veia una empresa inventada. Para eso hacia falta darle
 * una llave de produccion, que es la de los que pagan.
 *
 * Con esto, la misma pestaña reparte las dos: de ejemplo para integrar, reales
 * con un tope corto para convencer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consulta_llaves', function (Blueprint $tabla) {
            $tabla->boolean('datos_reales')->default(false)->after('entorno');

            // Tope propio, aparte del plan: una llave para enseñar el servicio
            // se queda en unas pocas consultas, no en las del plan mas barato.
            $tabla->unsignedSmallInteger('tope_pruebas')->nullable()->after('datos_reales');
        });
    }

    public function down(): void
    {
        Schema::table('consulta_llaves', function (Blueprint $tabla) {
            $tabla->dropColumn(['datos_reales', 'tope_pruebas']);
        });
    }
};
