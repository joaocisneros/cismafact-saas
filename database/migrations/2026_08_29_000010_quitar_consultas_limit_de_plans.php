<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los planes de facturacion no saben nada de consultas.
 *
 * Esta columna se añadio cuando las consultas iban a colgar de los planes de
 * facturacion. Luego se vio que no: quien compra consultas puede no facturar,
 * asi que tienen sus propios planes (api_planes) y sus propias llaves.
 *
 * Se quita porque una columna que nadie lee acaba leyendola alguien: el dia
 * que un plan de facturacion diga "1000 consultas" y el de consultas diga otra
 * cosa, nadie sabra cual manda.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('plans', 'consultas_limit')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('consultas_limit');
            });
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('consultas_limit')->default(0)->after('api_request_limit');
        });
    }
};
