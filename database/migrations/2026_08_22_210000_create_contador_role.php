<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rol "contador": entra al panel de Super Admin pero solo a la parte de
 * consulta —empresas, documentos y reportes— y puede entrar como soporte a una
 * empresa. No ve usuarios, planes, suscripciones, pagos, API, certificados,
 * configuracion ni auditoria, y no puede crear, suspender ni eliminar empresas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('roles')->where('name', 'contador')->exists()) {
            return;
        }

        DB::table('roles')->insert([
            'name' => 'contador',
            'display_name' => 'Contador',
            'description' => 'Consulta empresas, documentos y reportes de toda la plataforma, y puede entrar como soporte a una empresa. Sin acceso a usuarios, planes, pagos ni configuracion.',
            'permissions' => json_encode([
                'companies.view',
                'documents.view',
                'reports.view',
                'companies.impersonate',
            ]),
            'is_system' => true,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Solo se borra si ningun usuario lo tiene asignado.
        $rol = DB::table('roles')->where('name', 'contador')->first();

        if (! $rol) {
            return;
        }

        if (DB::table('users')->where('role_id', $rol->id)->exists()) {
            return;
        }

        DB::table('roles')->where('id', $rol->id)->delete();
    }
};
