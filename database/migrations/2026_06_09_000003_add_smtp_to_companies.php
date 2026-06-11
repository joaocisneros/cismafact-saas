<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuracion de correo (SMTP) propia de cada empresa. Los comprobantes
     * se envian a los clientes desde el correo de la propia empresa, no desde
     * uno generico de la plataforma. La contrasena va encriptada (cast en el
     * modelo) por eso la columna es TEXT.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('smtp_host', 100)->nullable()->after('email');
            $table->integer('smtp_port')->nullable()->after('smtp_host');
            $table->string('smtp_username', 150)->nullable()->after('smtp_port');
            $table->text('smtp_password')->nullable()->after('smtp_username');
            $table->string('smtp_encryption', 10)->nullable()->after('smtp_password'); // tls | ssl | null
            $table->string('mail_from_name', 150)->nullable()->after('smtp_encryption');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'smtp_host',
                'smtp_port',
                'smtp_username',
                'smtp_password',
                'smtp_encryption',
                'mail_from_name',
            ]);
        });
    }
};
