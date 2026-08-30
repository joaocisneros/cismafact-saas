<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las APIs como cosas que se administran, no como rutas fijas en el codigo.
 *
 * Antes RUC y DNI eran dos endpoints escritos a mano y una sola cuota metida
 * en la tabla de planes. Eso no deja hacer lo que hace falta: apagar una sin
 * tocar la otra, cobrarlas distinto, o dejar una en pruebas mientras se ajusta.
 *
 * Dos tablas:
 *
 *   apis            que se ofrece, si esta encendida y si esta en pruebas.
 *   api_plan_limite cuanto incluye cada plan de cada api. En la pivote y no en
 *                   plans porque una columna por api obligaria a una migracion
 *                   cada vez que se añada una.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apis', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug', 40)->unique();      // ruc, dni…
            $table->string('descripcion')->nullable();

            // Apagada: responde 503 y no gasta nada. Sirve para cortar cuando
            // el proveedor esta caido, en vez de dejar que cada cliente se
            // coma el error.
            $table->boolean('activa')->default(true);

            // En pruebas: devuelve un ejemplo fijo sin salir a internet ni
            // gastar cuota. Para que un cliente integre sin consumir.
            $table->boolean('modo_prueba')->default(false);

            $table->timestamps();
        });

        Schema::create('api_plan_limite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_id')->constrained('apis')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->unsignedInteger('limite_mensual')->default(0);
            $table->timestamps();

            $table->unique(['api_id', 'plan_id']);
        });

        // El consumo pasa a saber de que api fue.
        Schema::table('consultas_consumo', function (Blueprint $table) {
            $table->foreignId('api_id')->nullable()->after('company_id')->constrained('apis')->nullOnDelete();
        });

        $ahora = now();

        $apis = [
            ['nombre' => 'Consulta RUC', 'slug' => 'ruc', 'descripcion' => 'Razón social, estado, condición y domicilio fiscal'],
            ['nombre' => 'Consulta DNI', 'slug' => 'dni', 'descripcion' => 'Nombre completo y apellidos por separado'],
        ];

        foreach ($apis as $api) {
            $id = DB::table('apis')->insertGetId($api + [
                'activa' => true,
                'modo_prueba' => false,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            // De partida, cada api hereda la cuota que ya tenia el plan, para
            // que nadie se quede sin servicio al aplicar esto.
            foreach (DB::table('plans')->get() as $plan) {
                DB::table('api_plan_limite')->insert([
                    'api_id' => $id,
                    'plan_id' => $plan->id,
                    'limite_mensual' => $plan->consultas_limit ?? 0,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }
        }

        // Lo ya consumido se reparte a su api por el tipo que quedo anotado.
        foreach (DB::table('apis')->get() as $api) {
            DB::table('consultas_consumo')->where('tipo', $api->slug)->update(['api_id' => $api->id]);
        }
    }

    public function down(): void
    {
        Schema::table('consultas_consumo', function (Blueprint $table) {
            $table->dropConstrainedForeignId('api_id');
        });

        Schema::dropIfExists('api_plan_limite');
        Schema::dropIfExists('apis');
    }
};
