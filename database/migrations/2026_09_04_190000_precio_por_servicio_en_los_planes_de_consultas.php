<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El precio deja de ser del plan y pasa a ser de cada servicio.
 *
 * Antes el plan valia S/15 fuera lo que fuera que contrataras, asi que quien
 * solo queria RUC pagaba igual que quien se llevaba RUC y DNI, y encima veia
 * en pantalla un tope de DNI que no podia gastar. Ahora cada servicio tiene su
 * precio dentro del plan y lo que se cobra es la suma de los contratados.
 *
 * El reparto conserva lo de antes: RUC + DNI da el mismo total que el plan
 * costaba, para no tener que renegociar con quien ya tiene una llave.
 *
 * Al DNI le toca el doble que al RUC, y no al reves: el RUC es publico y lo
 * da la propia SUNAT, asi que cualquiera lo consigue; el de RENIEC no, y ahi
 * esta lo que de verdad se vende. Se cobra por lo dificil de conseguir, no por
 * lo que trae mas campos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_plan_limite', function (Blueprint $table) {
            $table->decimal('precio_mensual', 10, 2)->default(0)->after('limite_mensual');
        });

        // Dos tercios al DNI y uno al RUC, redondeado a soles enteros y
        // cuadrando el resto en el DNI para que la suma de exactamente lo que
        // el plan costaba.
        foreach (DB::table('api_planes')->get() as $plan) {
            if ($plan->a_medida || (float) $plan->precio_mensual <= 0) {
                continue;
            }

            $ruc = round((float) $plan->precio_mensual / 3);
            $dni = (float) $plan->precio_mensual - $ruc;

            foreach (['ruc' => $ruc, 'dni' => $dni] as $slug => $precio) {
                DB::table('api_plan_limite')
                    ->where('api_plan_id', $plan->id)
                    ->where('api_id', DB::table('apis')->where('slug', $slug)->value('id'))
                    ->update(['precio_mensual' => $precio]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('api_plan_limite', function (Blueprint $table) {
            $table->dropColumn('precio_mensual');
        });
    }
};
