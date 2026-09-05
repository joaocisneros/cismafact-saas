<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pro regalaba diez veces mas consultas por dos veces y media el precio.
 *
 * Cada consulta de RUC salia a S/0.0050 en Basico y a S/0.0013 en Pro: cuatro
 * veces mas barata. Con esa diferencia nadie se queda en Basico —por S/8 mas
 * te llevas diez veces mas— asi que Basico solo servia para que no lo comprara
 * nadie, y todo el mundo acababa en Pro pagando poco por mucho.
 *
 * Los topes bajan y los precios no se tocan: nadie paga mas que ayer. El
 * escalon queda en cuatro veces el volumen por dos veces y media el precio, o
 * sea un descuento por cantidad del 35% en vez del 75%.
 *
 * Empresarial baja en la misma proporcion. Es a convenir, asi que el tope es
 * orientativo, pero dejarlo en 50.000 lo separaba doce escalones de Pro y ya
 * no decia nada de lo que se acuerda.
 */
return new class extends Migration
{
    /** Lo de antes, por si hay que volver. */
    private const ANTES = [
        'pro' => ['ruc' => 10000, 'dni' => 2000],
        'empresarial' => ['ruc' => 50000, 'dni' => 10000],
    ];

    private const AHORA = [
        'pro' => ['ruc' => 4000, 'dni' => 1200],
        'empresarial' => ['ruc' => 20000, 'dni' => 6000],
    ];

    public function up(): void
    {
        $this->aplicar(self::AHORA);
    }

    public function down(): void
    {
        $this->aplicar(self::ANTES);
    }

    private function aplicar(array $topes): void
    {
        foreach ($topes as $slug => $porServicio) {
            $plan = DB::table('api_planes')->where('slug', $slug)->value('id')
                ?? DB::table('api_planes')->whereRaw('LOWER(nombre) = ?', [$slug])->value('id');

            if (! $plan) {
                continue;
            }

            foreach ($porServicio as $api => $limite) {
                DB::table('api_plan_limite')
                    ->where('api_plan_id', $plan)
                    ->where('api_id', DB::table('apis')->where('slug', $api)->value('id'))
                    ->update(['limite_mensual' => $limite]);
            }
        }
    }
};
