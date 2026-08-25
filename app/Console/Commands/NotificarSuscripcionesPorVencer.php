<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\SuscripcionPorVencer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Avisa por correo del vencimiento del plan.
 *
 * Se avisa en dias concretos y no todos los dias, para no volverse ruido: a los
 * 7, 3 y 1 dia antes, el mismo dia, y una vez al dia siguiente de vencer, que
 * es cuando la empresa ya no puede entrar y el aviso dentro del sistema deja de
 * poder verse.
 */
class NotificarSuscripcionesPorVencer extends Command
{
    protected $signature = 'suscripciones:notificar-vencimiento';

    protected $description = 'Avisa por correo al dueño de cada empresa cuando su plan está por vencer o acaba de vencer.';

    /** Días antes del vencimiento en los que se avisa (y +1 después). */
    private const DIAS_DE_AVISO = [7, 3, 1, 0, -1];

    public function handle(): int
    {
        $suscripciones = Subscription::with(['company.users.role'])
            ->whereNotNull('ends_at')
            ->whereIn('status', ['trial', 'active', 'expired'])
            ->get();

        $enviadas = 0;

        foreach ($suscripciones as $suscripcion) {
            $dias = (int) now()->startOfDay()->diffInDays($suscripcion->ends_at->startOfDay(), false);

            if (! in_array($dias, self::DIAS_DE_AVISO, true)) {
                continue;
            }

            // Una renovación automática no necesita aviso: no se va a cortar.
            if ($suscripcion->auto_renew && $dias >= 0) {
                continue;
            }

            $company = $suscripcion->company;

            if (! $company) {
                continue;
            }

            // Solo al dueño: un empleado no puede renovar nada.
            $duenos = $company->users->filter(
                fn ($user) => optional($user->role)->name === 'company_admin' && $user->active
            );

            if ($duenos->isEmpty()) {
                $this->line("  · {$company->razon_social}: sin administrador activo, no se avisa");
                continue;
            }

            Notification::send($duenos, new SuscripcionPorVencer(
                $company,
                $dias,
                $suscripcion->ends_at->format('d/m/Y'),
            ));

            $enviadas += $duenos->count();

            $cuando = $dias < 0 ? 'vencido' : ($dias === 0 ? 'vence hoy' : "vence en {$dias} día(s)");
            $this->line("  • {$company->razon_social}: {$cuando} → {$duenos->count()} administrador(es)");
        }

        $this->info("Avisos de vencimiento enviados: {$enviadas}");

        return self::SUCCESS;
    }
}
