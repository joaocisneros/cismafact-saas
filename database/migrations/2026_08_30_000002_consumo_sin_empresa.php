<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una consulta puede no ser de ninguna empresa de casa.
 *
 * La columna exigia empresa, pero el negocio del modulo es justo el contrario:
 * vender consultas a gente de fuera, que no tiene empresa en el sistema. A esos
 * les reventaba cada consulta con un error 500 al ir a anotarla. No se vio
 * antes porque todas las pruebas se hicieron con llaves atadas a una empresa.
 *
 * De paso, la clave dejaba de existir en cascada: borrar una empresa se llevaba
 * su historial de consumo, o sea la contabilidad del mes ya cobrada. Pasa a
 * quedarse en nulo, igual que la llave, que ya lo hacia bien: se va quien
 * consulto, se queda lo que se consumio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultas_consumo', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('consultas_consumo', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('consultas_consumo', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('consultas_consumo', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
    }
};
