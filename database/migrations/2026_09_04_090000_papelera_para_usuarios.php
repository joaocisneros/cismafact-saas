<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Papelera para los usuarios.
 *
 * Las empresas ya la tenían: al borrar una, se puede recuperar. Los usuarios
 * no, y borrar uno por error era definitivo —pasó, y no hubo forma de
 * devolverlos—. No hay razón para que unos sí y otros no: quien administra se
 * equivoca igual en las dos pantallas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
