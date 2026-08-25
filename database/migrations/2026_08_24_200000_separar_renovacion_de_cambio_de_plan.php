<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Separa "renovación" de "cambio de plan" en los motivos de ticket.
 *
 * Estaban juntos, pero para quien atiende no es lo mismo: renovar es extender
 * la fecha del mismo plan (Suscripciones → Renovar), y cambiar implica otro
 * plan y otro precio, que es una conversación distinta. Distinguirlo se ve en
 * la bandeja sin abrir el ticket.
 *
 * Lo ya registrado como "renovacion" se queda así: es lo que el cliente eligió.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tickets MODIFY COLUMN motivo ENUM('soporte','renovacion','cambio_plan','consulta') NOT NULL DEFAULT 'soporte'");
    }

    public function down(): void
    {
        DB::table('tickets')->where('motivo', 'cambio_plan')->update(['motivo' => 'renovacion']);

        DB::statement("ALTER TABLE tickets MODIFY COLUMN motivo ENUM('soporte','renovacion','consulta') NOT NULL DEFAULT 'soporte'");
    }
};
