<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deja que el titular de una llave de RUC y DNI entre a ver lo suyo.
 *
 * Hasta ahora, para saber cuánta cuota le quedaba o recuperar su secreto tenía
 * que escribir. Con esto entra con su correo y su contraseña —nunca con la
 * llave, que es para que su programa llame a la API— y ve su consumo, sus
 * consultas y nada más del sistema.
 *
 * El acceso cuelga del usuario y no de la llave: quien tenga dos llaves de
 * producción entra una sola vez y ve las dos. Atarlo a la llave le habría dado
 * dos contraseñas para lo mismo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consulta_llaves', function (Blueprint $tabla) {
            $tabla->foreignId('usuario_id')
                ->nullable()
                ->after('company_id')
                ->comment('Quién entra al panel a ver esta llave')
                ->constrained('users')
                ->nullOnDelete();
        });

        // El rol de quien solo consulta RUC y DNI. No es una empresa: no
        // factura, no tiene RUC emisor ni certificado, solo su llave.
        if (! DB::table('roles')->where('name', 'cliente_consultas')->exists()) {
            DB::table('roles')->insert([
                'name' => 'cliente_consultas',
                'display_name' => 'Cliente de RUC y DNI',
                'description' => 'Entra a ver su llave, su consumo y sus consultas. No ve nada más del sistema.',
                'permissions' => json_encode([]),
                'is_system' => true,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('consulta_llaves', function (Blueprint $tabla) {
            $tabla->dropConstrainedForeignId('usuario_id');
        });

        DB::table('roles')->where('name', 'cliente_consultas')->delete();
    }
};
