<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Notifications\CertificadoPorVencer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class NotificarCertificadosPorVencer extends Command
{
    protected $signature = 'certificados:notificar-vencimiento';

    protected $description = 'Avisa a cada empresa cuando su certificado digital esta por vencer (<=30 dias) o ya vencio.';

    public function handle(): int
    {
        $companies = Company::with('users')->get();
        $enviadas = 0;

        foreach ($companies as $company) {
            $estado = $company->certEstado();

            // Solo notificamos a quienes tienen certificado por vencer o vencido.
            if (! in_array($estado, ['por_vencer', 'vencido'], true)) {
                continue;
            }

            $usuarios = $company->users;
            if ($usuarios->isEmpty()) {
                continue;
            }

            Notification::send($usuarios, new CertificadoPorVencer($company, $company->certDiasRestantes()));
            $enviadas += $usuarios->count();

            $this->line("  • {$company->razon_social}: certificado {$estado} ({$company->certDiasRestantes()} dias) → {$usuarios->count()} usuario(s)");
        }

        $this->info("Notificaciones de certificado enviadas: {$enviadas}");

        return self::SUCCESS;
    }
}
