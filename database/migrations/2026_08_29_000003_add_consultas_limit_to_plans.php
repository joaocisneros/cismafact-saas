<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cuota de consultas de RUC y DNI, por plan.
 *
 * Va aqui y no en una tabla aparte porque es un limite del plan, como los tres
 * que ya hay. Asi se edita donde se editan los demas y el cliente ve todos sus
 * topes en el mismo sitio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('consultas_limit')->default(0)->after('api_request_limit');
        });

        // Valores de partida proporcionales a lo que ya permite cada plan.
        foreach (['Free' => 100, 'Pro' => 2000, 'Business' => 10000] as $nombre => $tope) {
            DB::table('plans')->where('name', $nombre)->update(['consultas_limit' => $tope]);
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('consultas_limit');
        });
    }
};
