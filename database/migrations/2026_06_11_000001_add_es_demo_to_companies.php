<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca la(s) empresa(s) usada(s) como SANDBOX/DEMO para que los devs
     * prueben la API sin registrarse. Una empresa es_demo:
     *  - no cuenta como cliente real en estadísticas,
     *  - no se le aplican los topes del plan (emite ilimitado en beta).
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('es_demo')->default(false)->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('es_demo');
        });
    }
};
