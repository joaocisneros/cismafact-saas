<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'logo')) {
                $table->string('logo')->nullable()->after('email');
            }
            if (!Schema::hasColumn('companies', 'direccion')) {
                $table->string('direccion')->nullable()->after('nombre_comercial');
            }
            if (!Schema::hasColumn('companies', 'telefono')) {
                $table->string('telefono')->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['logo', 'direccion', 'telefono']);
        });
    }
};
