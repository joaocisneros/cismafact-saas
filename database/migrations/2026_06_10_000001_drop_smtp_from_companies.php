<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Se eliminó la función de envío de comprobantes por correo PROPIO de cada
 * empresa. Por eso se quitan las columnas SMTP de companies (no se usaban en
 * producción; nadie las había configurado). El correo global de la plataforma
 * (tabla settings, Super Admin) es independiente y se conserva.
 */
return new class extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('smtp_host', 100)->nullable()->after('email');
            $table->integer('smtp_port')->nullable()->after('smtp_host');
            $table->string('smtp_username', 150)->nullable()->after('smtp_port');
            $table->text('smtp_password')->nullable()->after('smtp_username');
            $table->string('smtp_encryption', 10)->nullable()->after('smtp_password');
            $table->string('mail_from_name', 150)->nullable()->after('smtp_encryption');
        });
    }
};
