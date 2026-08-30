<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Planes propios para las consultas de RUC y DNI.
 *
 * Aparte de los de facturacion a proposito. Quien compra la consulta no
 * necesariamente factura con el sistema: obligarle a contratar un plan de
 * facturacion para usar una API seria cobrarle por algo que no va a usar.
 *
 * Y al reves: una empresa que si factura puede querer mas consultas de las que
 * trae su plan de facturacion, sin tener que subir de plan entero.
 *
 * El limite de cada api en cada plan vive en la pivote api_plan_limite, no en
 * una columna aqui: una columna por api obligaria a migrar cada vez que se
 * añada una.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_planes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->string('slug', 40)->unique();
            $table->string('descripcion')->nullable();
            $table->decimal('precio_mensual', 10, 2)->default(0);

            // "Empresarial" no lleva precio ni se contrata solo: se habla. Con
            // esto la tarjeta enseña "Personalizado" y un boton de contacto en
            // vez de un importe inventado.
            $table->boolean('a_medida')->default(false);

            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        // La pivote pasa a colgar de estos planes, no de los de facturacion.
        Schema::dropIfExists('api_plan_limite');

        Schema::create('api_plan_limite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_id')->constrained('apis')->cascadeOnDelete();
            $table->foreignId('api_plan_id')->constrained('api_planes')->cascadeOnDelete();
            $table->unsignedInteger('limite_mensual')->default(0);
            $table->timestamps();

            $table->unique(['api_id', 'api_plan_id']);
        });

        // Quien consume la API tiene su plan de API, independiente del de
        // facturacion. Sin plan no hay acceso, que es lo prudente.
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('api_plan_id')->nullable()->after('plan_id')
                ->constrained('api_planes')->nullOnDelete();
        });

        $ahora = now();

        $planes = [
            ['nombre' => 'Gratis', 'slug' => 'gratis', 'descripcion' => 'Para probar', 'precio_mensual' => 0, 'a_medida' => false, 'orden' => 1, 'cuota' => 100],
            ['nombre' => 'Básico', 'slug' => 'basico', 'descripcion' => 'Para empezar', 'precio_mensual' => 29, 'a_medida' => false, 'orden' => 2, 'cuota' => 1000],
            ['nombre' => 'Pro', 'slug' => 'pro', 'descripcion' => 'Uso habitual', 'precio_mensual' => 79, 'a_medida' => false, 'orden' => 3, 'cuota' => 5000],
            ['nombre' => 'Empresarial', 'slug' => 'empresarial', 'descripcion' => 'A tu medida', 'precio_mensual' => 0, 'a_medida' => true, 'orden' => 4, 'cuota' => 20000],
        ];

        $apis = DB::table('apis')->pluck('id');

        foreach ($planes as $plan) {
            $cuota = $plan['cuota'];
            unset($plan['cuota']);

            $id = DB::table('api_planes')->insertGetId($plan + [
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            // De partida, las dos apis incluyen lo mismo en cada plan. Se
            // cambia luego una por una desde el panel.
            foreach ($apis as $api) {
                DB::table('api_plan_limite')->insert([
                    'api_id' => $api,
                    'api_plan_id' => $id,
                    'limite_mensual' => $cuota,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }
        }

        // Las empresas que ya estaban arrancan en el gratuito, para que nadie
        // se quede sin servicio al aplicar esto.
        $gratis = DB::table('api_planes')->where('slug', 'gratis')->value('id');
        DB::table('companies')->update(['api_plan_id' => $gratis]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('api_plan_id');
        });

        Schema::dropIfExists('api_plan_limite');
        Schema::dropIfExists('api_planes');

        Schema::create('api_plan_limite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_id')->constrained('apis')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->unsignedInteger('limite_mensual')->default(0);
            $table->timestamps();
            $table->unique(['api_id', 'plan_id']);
        });
    }
};
