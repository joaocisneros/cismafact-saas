<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro local de la baja de una guía de remisión.
 *
 * SUNAT no deja anular una GRE desde el sistema del contribuyente: su propia
 * documentación dice que "la comunicación se debe realizar a través de la
 * opción que contemple el SEE - SOL", y ningún OSE o PSE puede hacerlo. Se da
 * de baja a mano en el portal, con la Clave SOL.
 *
 * Como el trámite ocurre fuera, aquí solo se deja constancia: quién la registró
 * y cuándo, para que la guía deje de figurar como vigente en los listados. No
 * se envía nada a SUNAT desde este campo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('dispatch_guides', 'anulado_en')) {
            return;
        }

        Schema::table('dispatch_guides', function (Blueprint $table) {
            $table->timestamp('anulado_en')->nullable()->after('estado_sunat');
            $table->string('anulado_motivo', 250)->nullable()->after('anulado_en');
            $table->string('anulado_registrado_por', 100)->nullable()->after('anulado_motivo');

            $table->index('anulado_en');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('dispatch_guides', 'anulado_en')) {
            return;
        }

        Schema::table('dispatch_guides', function (Blueprint $table) {
            $table->dropIndex(['anulado_en']);
            $table->dropColumn(['anulado_en', 'anulado_motivo', 'anulado_registrado_por']);
        });
    }
};
