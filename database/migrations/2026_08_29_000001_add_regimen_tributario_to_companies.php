<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El regimen tributario de la empresa.
 *
 * No va en el XML: SUNAT no lo pide en el comprobante. Importa por lo que
 * permite emitir. En Nuevo RUS solo se pueden dar boletas, no facturas, y sin
 * este dato el sistema no puede avisar antes de que SUNAT rechace.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('regimen_tributario', 20)->nullable()->after('nombre_comercial');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('regimen_tributario');
        });
    }
};
