<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * De donde viene la consulta: de dentro o de fuera.
 *
 * No es lo mismo y no puede contarse igual:
 *
 *   interno  una empresa del sistema buscando un cliente para emitirle. Ya
 *            paga su plan, asi que no se le descuenta cuota. Se anota igual
 *            para saber cuanto se usa y cuanto cuesta mantenerlo.
 *
 *   externo  alguien que compro la API. A ese si se le cuenta contra el
 *            limite de su plan, porque es el servicio que se le vende.
 *
 * Sin esta distincion, una empresa que emite mucho se quedaria sin consultas
 * por usar el sistema que ya paga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultas_consumo', function (Blueprint $table) {
            $table->enum('origen', ['interno', 'externo'])->default('externo')->after('api_id');
            $table->index(['origen', 'created_at']);
        });

        // Lo ya anotado vino de la API, que es lo unico que existia.
        DB::table('consultas_consumo')->update(['origen' => 'externo']);
    }

    public function down(): void
    {
        Schema::table('consultas_consumo', function (Blueprint $table) {
            $table->dropIndex(['origen', 'created_at']);
            $table->dropColumn('origen');
        });
    }
};
