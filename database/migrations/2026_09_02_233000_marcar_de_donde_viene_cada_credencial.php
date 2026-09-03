<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * De dónde viene cada credencial: la reparte el Super Admin desde Sandbox
 * Facturación, o la crea la propia empresa desde su panel.
 *
 * Sin esto eran filas iguales en la misma tabla. Como los tokens de sandbox
 * cuelgan de la empresa demo, al dueño de esa empresa le salían en su panel
 * junto a las suyas y podía regenerarlos o borrarlos: el programador al que
 * se los habían entregado se quedaba sin servicio sin que nadie se enterara.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->string('origen', 20)->default('empresa')->after('name')->index();
        });

        // Las que ya existen: las de sandbox se reconocen por su nombre, que es
        // como las viene creando el módulo desde el principio.
        DB::table('api_keys')->where('name', 'like', 'Sandbox - %')->update(['origen' => 'sandbox']);
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn('origen');
        });
    }
};
