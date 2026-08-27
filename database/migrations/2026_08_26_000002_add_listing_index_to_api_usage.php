<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice para listar la actividad de una credencial.
 *
 * La pantalla pide siempre lo mismo: las llamadas de un token, de la más
 * reciente hacia atrás. Con el índice suelto de `api_key_id`, MySQL filtraba
 * por él y después ordenaba a mano (Using filesort): con unos cientos de filas
 * ni se nota, pero un token que acumule cien mil llamadas obligaría a ordenar
 * cien mil filas cada vez que se abre la ventana.
 *
 * Con el índice compuesto el orden ya viene dado y solo se leen las 25 que se
 * van a enseñar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('api_usage')) {
            return;
        }

        Schema::table('api_usage', function (Blueprint $table) {
            $table->index(['api_key_id', 'created_at'], 'api_usage_key_fecha_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('api_usage')) {
            return;
        }

        Schema::table('api_usage', function (Blueprint $table) {
            $table->dropIndex('api_usage_key_fecha_index');
        });
    }
};
