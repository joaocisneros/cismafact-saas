<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Correlative;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanyDemoSeeder extends Seeder
{
    public function run(): void
    {
        $demoSecret = 'cf_demo_secret_1234567890123456789012345678901234567890123456789012345678901234';

        $company = Company::updateOrCreate(
            ['ruc' => '20161515648'],
            [
                'razon_social' => 'CISMA FACT TEST S.A.C.',
                'nombre_comercial' => 'Cisma Fact Test',
                'direccion' => 'Av. Principal 123, Lima',
                'ubigeo' => '150101',
                'distrito' => 'Lima',
                'provincia' => 'Lima',
                'departamento' => 'Lima',
                'email' => 'demo@cismafact.com',
                'usuario_sol' => 'MODDATOS',
                'clave_sol' => 'MODDATOS',
                'activo' => true,
            ]
        );

        $branch = Branch::updateOrCreate(
            ['company_id' => $company->id, 'codigo' => '001'],
            [
                'nombre' => 'Sucursal Principal',
                'direccion' => 'Av. Principal 123, Lima',
                'ubigeo' => '150101',
                'distrito' => 'Lima',
                'provincia' => 'Lima',
                'departamento' => 'Lima',
                'activo' => true,
            ]
        );

        $documentTypes = [
            ['tipo_documento' => '01', 'serie' => 'F001'],
            ['tipo_documento' => '01', 'serie' => 'F002'],
            ['tipo_documento' => '03', 'serie' => 'B001'],
            ['tipo_documento' => '03', 'serie' => 'B002'],
            ['tipo_documento' => '07', 'serie' => 'FC01'],
            ['tipo_documento' => '07', 'serie' => 'BC01'],
            ['tipo_documento' => '08', 'serie' => 'FD01'],
            ['tipo_documento' => '08', 'serie' => 'BD01'],
            ['tipo_documento' => '09', 'serie' => 'T001'],
        ];

        foreach ($documentTypes as $dt) {
            Correlative::updateOrCreate(
                ['branch_id' => $branch->id ?? $branch->id, 'tipo_documento' => $dt['tipo_documento'], 'serie' => $dt['serie']],
                ['correlativo_actual' => 0]
            );
        }

        $companyAdminRole = Role::where('name', 'company_admin')->first();

        User::updateOrCreate(
            ['email' => 'empresa@demo.com'],
            [
                'name' => 'Empresa Demo',
                'password' => Hash::make('password'),
                'role_id' => $companyAdminRole?->id,
                'company_id' => $company->id,
                'user_type' => 'user',
                'active' => true,
                'email_verified_at' => now(),
            ]
        );

        ApiKey::updateOrCreate(
            ['key' => 'cf_demo_key_abc123def456ghi789jkl012mno345pqr678stu901vwx234'],
            [
                'company_id' => $company->id,
                'name' => 'API Demo',
                'secret' => Hash::make($demoSecret),
                'plain_secret' => $demoSecret,
                'abilities' => ['*'],
                'active' => true,
            ]
        );

        $this->command?->info('Empresa demo creada: empresa@demo.com / password');
    }
}
