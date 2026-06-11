<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Campos sensibles que ahora se guardan encriptados. Esta migracion cifra
     * los valores en texto plano que ya existian en la base de datos.
     */
    private array $fields = [
        'clave_sol',
        'certificado_password',
        'gre_clave_sol',
        'gre_client_secret_beta',
        'gre_client_secret_produccion',
    ];

    public function up(): void
    {
        // Acceso raw (DB::table) para no pasar por el cast 'encrypted' del modelo.
        foreach (DB::table('companies')->get() as $company) {
            $updates = [];

            foreach ($this->fields as $field) {
                $value = $company->$field ?? null;
                if ($value === null || $value === '') {
                    continue;
                }

                // Si ya esta encriptado, no lo tocamos (idempotente).
                try {
                    Crypt::decryptString($value);
                    continue;
                } catch (\Throwable $e) {
                    // Es texto plano -> encriptar.
                }

                $updates[$field] = Crypt::encryptString($value);
            }

            if (! empty($updates)) {
                DB::table('companies')->where('id', $company->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Revertir: desencriptar de vuelta a texto plano.
        foreach (DB::table('companies')->get() as $company) {
            $updates = [];

            foreach ($this->fields as $field) {
                $value = $company->$field ?? null;
                if ($value === null || $value === '') {
                    continue;
                }

                try {
                    $updates[$field] = Crypt::decryptString($value);
                } catch (\Throwable $e) {
                    // Ya era texto plano.
                }
            }

            if (! empty($updates)) {
                DB::table('companies')->where('id', $company->id)->update($updates);
            }
        }
    }
};
