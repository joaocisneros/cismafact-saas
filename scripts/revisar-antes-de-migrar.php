<?php

/*
 * Que va a cambiar en produccion antes de tocar nada.
 *
 * Se ejecuta ANTES del migrate, en el servidor:
 *
 *     php scripts/revisar-antes-de-migrar.php
 *
 * No modifica nada: solo mira y avisa. Lo que de verdad hay que ver es si
 * alguien esta consumiendo por encima de los topes nuevos, porque a ese hay
 * que escribirle antes de bajarselos.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ApiPlan;
use App\Models\ConsultaLlave;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$linea = fn () => print(str_repeat('─', 74) . "\n");
$avisos = [];

echo "\n";
$linea();
echo "  QUÉ VA A CAMBIAR AL MIGRAR\n";
$linea();

// ------------------------------------------------------- Si ya está migrado
$yaEstaba = Schema::hasColumn('api_plan_limite', 'precio_mensual');

printf("\n  Estado: %s\n\n", $yaEstaba
    ? 'las migraciones de precios YA están aplicadas'
    : 'las migraciones de precios están PENDIENTES');

// ------------------------------------------------------------- Los planes
echo "  LOS PLANES DE CONSULTAS AHORA MISMO\n\n";

foreach (ApiPlan::with('apis')->orderBy('orden')->get() as $plan) {
    printf("    %-14s slug=%-14s %s\n",
        $plan->nombre,
        $plan->slug,
        $plan->a_medida ? 'a convenir' : 'S/ ' . number_format((float) $plan->precio_mensual, 2));

    foreach ($plan->apis as $api) {
        printf("       %-4s %s consultas\n",
            strtoupper($api->slug),
            number_format((int) $api->pivot->limite_mensual));
    }
}

/*
 * Los topes que va a poner la migracion. Si aqui aparece un plan que en este
 * servidor se llama de otra forma, la migracion no lo encontrara y habra que
 * ajustarlo a mano.
 */
$topesNuevos = [
    'pro' => ['ruc' => 4000, 'dni' => 1200],
    'empresarial' => ['ruc' => 20000, 'dni' => 6000],
];

echo "\n";
$linea();
echo "  1. LOS TOPES QUE BAJAN\n";
$linea();
echo "\n";

foreach ($topesNuevos as $slug => $nuevos) {
    $plan = ApiPlan::with('apis')
        ->where('slug', $slug)
        ->orWhereRaw('LOWER(nombre) = ?', [$slug])
        ->first();

    if (! $plan) {
        $avisos[] = "No hay ningún plan «{$slug}»: la migración no le bajará los topes. "
            . 'Revisa cómo se llama aquí.';

        printf("    %-14s NO EXISTE en este servidor\n", $slug);

        continue;
    }

    foreach ($nuevos as $api => $tope) {
        $actual = (int) ($plan->apis->firstWhere('slug', $api)?->pivot->limite_mensual ?? 0);

        printf("    %-14s %-4s  %s  →  %s%s\n",
            $plan->nombre,
            strtoupper($api),
            str_pad(number_format($actual), 7, ' ', STR_PAD_LEFT),
            str_pad(number_format($tope), 7, ' ', STR_PAD_LEFT),
            $actual > $tope ? '   (baja)' : ($actual === $tope ? '   (igual)' : '   (sube)'));
    }
}

// ------------------------------------------ Quien se queda corto con eso
echo "\n";
$linea();
echo "  2. A QUIÉN LE AFECTA — lo importante\n";
$linea();
echo "\n";

$mes = now()->startOfMonth();
$afectados = [];

foreach (ConsultaLlave::with('plan.apis')->where('entorno', 'produccion')->get() as $llave) {
    $slug = $llave->plan?->slug;
    $nuevos = $topesNuevos[$slug] ?? null;

    if (! $nuevos) {
        continue;
    }

    foreach ((array) $llave->servicios as $servicio) {
        $tope = $nuevos[$servicio] ?? null;

        if ($tope === null) {
            continue;
        }

        $gastado = DB::table('consultas_consumo')
            ->where('llave_id', $llave->id)
            ->where('created_at', '>=', $mes)
            ->count();

        // Se avisa al 70% y no solo al pasarse: quien va por ahi a mitad de
        // mes llega al tope antes de fin de mes.
        if ($gastado > $tope * 0.7) {
            $afectados[] = sprintf('    %-38s %s: %s de %s este mes',
                mb_substr($llave->titular ?: $llave->nombre, 0, 38),
                strtoupper($servicio),
                number_format($gastado),
                number_format($tope));
        }
    }
}

if ($afectados) {
    echo implode("\n", $afectados), "\n\n";
    $avisos[] = count($afectados) . ' llave(s) van a quedarse cortas con los topes nuevos. '
        . 'Escríbeles antes de migrar, o déjalos en su tope actual.';
} else {
    echo "    Nadie está cerca de los topes nuevos. Se puede migrar sin avisar a nadie.\n\n";
}

// ----------------------------------------------------- Lo que hace falta
echo "\n";
$linea();
echo "  3. LO QUE TIENE QUE ESTAR EN EL .ENV\n";
$linea();
echo "\n";

$clave = config('asistente.clave');

printf("    OPENROUTER_API_KEY  %s\n", filled($clave)
    ? 'puesta (' . substr($clave, 0, 8) . '…)'
    : 'FALTA — sin ella el chat de la web no aparece');

if (blank($clave)) {
    $avisos[] = 'Falta OPENROUTER_API_KEY en el .env: el asistente no se pintará en la web.';
}

printf("    ASISTENTE_WHATSAPP  %s\n", config('asistente.whatsapp'));

// -------------------------------------------------------------- Resumen
echo "\n";
$linea();

if ($avisos) {
    echo "  MIRA ESTO ANTES DE MIGRAR\n";
    $linea();

    foreach ($avisos as $i => $aviso) {
        printf("\n  %d. %s\n", $i + 1, wordwrap($aviso, 68, "\n     "));
    }
} else {
    echo "  TODO EN ORDEN. Puedes migrar.\n";
}

echo "\n";
$linea();
echo "\n  Cuando termines de revisar:  php artisan migrate --force\n\n";
