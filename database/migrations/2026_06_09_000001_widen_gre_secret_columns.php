<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Los secretos GRE se guardan encriptados (cast 'encrypted'), lo que alarga
     * el valor muy por encima de VARCHAR(255). Se cambian a TEXT.
     */
    public function up(): void
    {
        foreach ([
            'gre_client_secret_beta',
            'gre_client_secret_produccion',
            'gre_clave_sol',
        ] as $col) {
            DB::statement("ALTER TABLE `companies` MODIFY `{$col}` TEXT NULL");
        }
    }

    public function down(): void
    {
        foreach ([
            'gre_client_secret_beta',
            'gre_client_secret_produccion',
            'gre_clave_sol',
        ] as $col) {
            DB::statement("ALTER TABLE `companies` MODIFY `{$col}` VARCHAR(255) NULL");
        }
    }
};
