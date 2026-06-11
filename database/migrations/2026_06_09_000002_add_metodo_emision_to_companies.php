<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Metodo de emision de la empresa:
     *  - 'cisma_fact'  : emite desde la plataforma (firma + envia + CDR). Requiere certificado.
     *  - 'sunat_manual': emite manualmente en el portal SUNAT. No requiere certificado.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('metodo_emision', 20)->default('cisma_fact')->after('plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('metodo_emision');
        });
    }
};
