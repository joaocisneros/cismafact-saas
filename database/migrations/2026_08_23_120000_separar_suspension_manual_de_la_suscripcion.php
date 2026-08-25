<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una empresa puede quedar sin acceso por dos motivos distintos que hasta ahora
 * compartian un unico campo ('activo'):
 *
 *   1. Su suscripcion vencio  -> lo decide el sistema.
 *   2. El Super Admin la suspendio -> lo decides tu.
 *
 * Como solo habia un campo, SubscriptionStatusService::synchronize() volvia a
 * poner 'activo' en 1 en cuanto la suscripcion seguia vigente, borrando la
 * suspension manual: el boton "Suspender empresa" no servia de nada. Con esta
 * bandera aparte, lo que apagas tu solo lo enciendes tu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('suspendida_manualmente')->default(false)->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('suspendida_manualmente');
        });
    }
};
