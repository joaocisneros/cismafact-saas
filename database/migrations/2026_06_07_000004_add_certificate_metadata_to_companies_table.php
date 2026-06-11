<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Metadata del certificado digital, extraida al subirlo. Permite
            // mostrar estado y alertas de vencimiento sin abrir el .pfx cada vez.
            $table->string('cert_titular')->nullable()->after('certificado_password');
            $table->string('cert_ruc', 15)->nullable()->after('cert_titular');
            $table->date('cert_valido_desde')->nullable()->after('cert_ruc');
            $table->date('cert_valido_hasta')->nullable()->after('cert_valido_desde');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['cert_titular', 'cert_ruc', 'cert_valido_desde', 'cert_valido_hasta']);
        });
    }
};
