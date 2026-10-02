<?php

namespace App\Console\Commands;

use App\Models\Boleta;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CrearDemoRendimiento extends Command
{
    protected $signature = 'demo:rendimiento-equipo
        {--empresa=Empresa Prueba Planes Cisma SAC}
        {--password=Prueba1234!}
        {--limpiar : Elimina solamente los comprobantes marcados como demo de rendimiento}';

    protected $description = 'Crea cinco empleados y comprobantes locales para probar el rendimiento del equipo';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Este comando solo se puede ejecutar en el entorno local.');
            return self::FAILURE;
        }

        $company = Company::where('razon_social', $this->option('empresa'))->first();
        if (! $company) {
            $this->error('No se encontro la empresa indicada.');
            return self::FAILURE;
        }

        if ($company->modo_produccion) {
            $this->error('La empresa esta en produccion. No se crearon datos de demostracion.');
            return self::FAILURE;
        }

        if ($this->option('limpiar')) {
            $facturas = Invoice::where('company_id', $company->id)
                ->where('datos_adicionales->demo_rendimiento', true)->count();
            $boletas = Boleta::where('company_id', $company->id)
                ->where('datos_adicionales->demo_rendimiento', true)->count();

            DB::transaction(function () use ($company) {
                Invoice::where('company_id', $company->id)
                    ->where('datos_adicionales->demo_rendimiento', true)->delete();
                Boleta::where('company_id', $company->id)
                    ->where('datos_adicionales->demo_rendimiento', true)->delete();
            });

            $this->info("Demo eliminada: {$facturas} facturas y {$boletas} boletas. Usuarios y documentos reales conservados.");
            return self::SUCCESS;
        }

        $role = Role::where('name', 'company_user')->first();
        $branch = $company->branches()->where('activo', true)->first();
        if (! $role || ! $branch) {
            $this->error('Falta el rol Empleado o una sucursal activa.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($company, $role, $branch) {
            $clienteFactura = Client::updateOrCreate(
                ['company_id' => $company->id, 'tipo_documento' => '6', 'numero_documento' => '20609999002'],
                ['razon_social' => 'CLIENTE DEMO RENDIMIENTO SAC', 'direccion' => 'LIMA', 'activo' => true]
            );
            $clienteBoleta = Client::updateOrCreate(
                ['company_id' => $company->id, 'tipo_documento' => '1', 'numero_documento' => '70000001'],
                ['razon_social' => 'CLIENTE DEMO', 'direccion' => 'LIMA', 'activo' => true]
            );

            foreach (range(1, 5) as $numero) {
                $usuario = User::withTrashed()->firstOrNew([
                    'email' => "vendedor{$numero}@pruebas.cisma.test",
                ]);
                $usuario->fill([
                    'name' => "Vendedor Prueba {$numero}",
                    'password' => $this->option('password'),
                    'role_id' => $role->id,
                    'company_id' => $company->id,
                    'user_type' => 'user',
                    'active' => true,
                    'force_password_change' => false,
                ])->forceFill([
                    'email_verified_at' => now(),
                    'deleted_at' => null,
                ])->save();

                $this->crearFactura($company->id, $branch->id, $clienteFactura->id, $usuario, $numero, 1, 100 * $numero, 'ACEPTADO');
                $this->crearBoleta($company->id, $branch->id, $clienteBoleta->id, $usuario, $numero, 2, 75 * $numero, 'ACEPTADO');
                $estado = $numero === 4 ? 'RECHAZADO' : ($numero === 3 ? 'PENDIENTE' : 'ACEPTADO');
                $this->crearFactura($company->id, $branch->id, $clienteFactura->id, $usuario, $numero, 3, 125 * $numero, $estado);
            }
        });

        $this->info('Demo creada: 5 usuarios y 15 comprobantes locales (sin envio a SUNAT).');
        $this->line('Clave comun: ' . $this->option('password'));
        return self::SUCCESS;
    }

    private function crearFactura(int $companyId, int $branchId, int $clientId, User $usuario, int $usuarioNumero, int $documentoNumero, float $total, string $estado): void
    {
        $correlativo = sprintf('9%05d%d', $usuarioNumero, $documentoNumero);
        $this->guardarDocumento(Invoice::class, $companyId, $branchId, $clientId, $usuario, 'FD01', $correlativo, $total, $estado, '01');
    }

    private function crearBoleta(int $companyId, int $branchId, int $clientId, User $usuario, int $usuarioNumero, int $documentoNumero, float $total, string $estado): void
    {
        $correlativo = sprintf('9%05d%d', $usuarioNumero, $documentoNumero);
        $this->guardarDocumento(Boleta::class, $companyId, $branchId, $clientId, $usuario, 'BD01', $correlativo, $total, $estado, '03');
    }

    private function guardarDocumento(string $modelo, int $companyId, int $branchId, int $clientId, User $usuario, string $serie, string $correlativo, float $total, string $estado, string $tipo): void
    {
        $gravada = round($total / 1.18, 2);
        $igv = round($total - $gravada, 2);

        $modelo::updateOrCreate(
            ['company_id' => $companyId, 'serie' => $serie, 'correlativo' => $correlativo],
            [
                'branch_id' => $branchId,
                'client_id' => $clientId,
                'created_by_user_id' => $usuario->id,
                'tipo_documento' => $tipo,
                'numero_completo' => "{$serie}-{$correlativo}",
                'fecha_emision' => now()->subDays(6 - $usuario->id % 5)->toDateString(),
                'valor_venta' => $gravada,
                'mto_oper_gravadas' => $gravada,
                'mto_igv' => $igv,
                'total_impuestos' => $igv,
                'sub_total' => $total,
                'mto_imp_venta' => $total,
                'detalles' => [[
                    'codigo' => 'DEMO-01',
                    'descripcion' => 'SERVICIO DE DEMOSTRACION',
                    'cantidad' => 1,
                    'valor_unitario' => $gravada,
                    'precio_unitario' => $total,
                    'igv' => $igv,
                ]],
                'estado_sunat' => $estado,
                'respuesta_sunat' => 'Dato local de demostracion. No fue enviado a SUNAT.',
                'usuario_creacion' => $usuario->name,
                'datos_adicionales' => ['demo_rendimiento' => true],
            ]
        );
    }
}
