<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La serie de comprobantes debe ser unica por EMPRESA, no por sucursal.
 *
 * El unico era (branch_id, tipo_documento, serie), asi que dos sucursales de la
 * misma empresa podian tener ambas la serie F001, cada una con su contador. Las
 * dos habrian emitido F001-000001 con el mismo RUC y SUNAT rechaza el duplicado.
 *
 * Se añade company_id a correlatives (copiado de su sucursal) y el unico pasa a
 * ser (company_id, tipo_documento, serie).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('correlatives', 'company_id')) {
            Schema::table('correlatives', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
            });
        }

        // Rellenar company_id a partir de la sucursal de cada serie.
        DB::statement('
            UPDATE correlatives c
            JOIN branches b ON b.id = c.branch_id
            SET c.company_id = b.company_id
            WHERE c.company_id IS NULL
        ');

        Schema::table('correlatives', function (Blueprint $table) {
            $table->dropUnique('correlatives_branch_id_tipo_documento_serie_unique');
            $table->unique(['company_id', 'tipo_documento', 'serie'], 'correlatives_company_documento_serie_unique');
        });
    }

    public function down(): void
    {
        Schema::table('correlatives', function (Blueprint $table) {
            $table->dropUnique('correlatives_company_documento_serie_unique');
            $table->unique(['branch_id', 'tipo_documento', 'serie'], 'correlatives_branch_id_tipo_documento_serie_unique');
            $table->dropColumn('company_id');
        });
    }
};
