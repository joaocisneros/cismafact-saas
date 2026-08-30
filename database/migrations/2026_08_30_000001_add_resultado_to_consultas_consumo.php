<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Como fue cada consulta, no solo que la hubo.
 *
 * Faltaba lo que hace util un historial: si salio bien y cuanto tardo. Sin eso
 * no se puede responder a "¿por que le falla al cliente?" ni a "¿esta lento el
 * proveedor?", que son las dos razones por las que alguien mira un registro.
 *
 * Y hasta ahora solo se anotaban las que salian bien, para no descontar cuota
 * por un numero mal escrito. Eso deja el historial cojo: los errores son
 * justamente lo que se busca. Se anotan todas y la cuota cuenta solo las
 * buenas, que es lo que hace falta separar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultas_consumo', function (Blueprint $table) {
            $table->boolean('exito')->default(true)->after('fuente');
            $table->unsignedSmallInteger('ms')->nullable()->after('exito');
            $table->string('motivo', 120)->nullable()->after('ms');

            // Contar lo bueno del mes de una llave es lo que mas se consulta.
            $table->index(['llave_id', 'exito', 'created_at'], 'idx_consumo_llave_exito');
        });

        // Lo ya anotado salio bien: era la unica forma de acabar en la tabla.
        DB::table('consultas_consumo')->update(['exito' => true]);
    }

    public function down(): void
    {
        Schema::table('consultas_consumo', function (Blueprint $table) {
            $table->dropIndex('idx_consumo_llave_exito');
            $table->dropColumn(['exito', 'ms', 'motivo']);
        });
    }
};
