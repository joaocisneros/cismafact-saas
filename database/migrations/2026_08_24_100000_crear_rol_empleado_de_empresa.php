<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rol para el personal de una empresa (cajeros, vendedores, asistentes).
 *
 * Hasta ahora los planes ofrecian 1, 5 y 20 usuarios, pero el cliente no tenia
 * ninguna pantalla para darlos de alta: solo el Super Admin podia crear
 * usuarios. En la practica todas las empresas se quedaban con un unico usuario
 * y los tres planes se comportaban igual.
 *
 * El empleado emite y consulta; lo que compromete a la empresa entera
 * (datos fiscales, configuracion SUNAT, API keys, plan, usuarios) queda para el
 * dueno (company_admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('roles')->where('name', 'company_user')->exists()) {
            return;
        }

        DB::table('roles')->insert([
            'name' => 'company_user',
            'display_name' => 'Empleado',
            'description' => 'Emite y consulta comprobantes de su empresa. No accede a datos de la empresa, configuración SUNAT, API keys, plan ni usuarios.',
            'permissions' => json_encode([]),
            'is_system' => true,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('role_id', DB::table('roles')->where('name', 'company_user')->value('id'))->update([
            'role_id' => DB::table('roles')->where('name', 'company_admin')->value('id'),
        ]);

        DB::table('roles')->where('name', 'company_user')->delete();
    }
};
