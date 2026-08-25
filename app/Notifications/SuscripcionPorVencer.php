<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de vencimiento del plan.
 *
 * Va por correo a proposito. El aviso dentro del sistema solo sirve mientras la
 * empresa aun puede entrar: en cuanto el plan vence, el acceso se corta y ese
 * canal deja de existir justo cuando mas falta hace. El correo llega igual.
 */
class SuscripcionPorVencer extends Notification
{
    use Queueable;

    public function __construct(
        private Company $company,
        private int $diasRestantes,
        private ?string $fechaVencimiento = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $fecha = $this->fechaVencimiento ?? '';
        $mensaje = (new MailMessage)->greeting("Hola {$notifiable->name},");

        if ($this->diasRestantes < 0) {
            return $mensaje
                ->subject("Tu plan venció — {$this->company->razon_social}")
                ->line("El plan de **{$this->company->razon_social}** venció el {$fecha}.")
                ->line('Mientras siga vencido no podrás iniciar sesión ni emitir comprobantes.')
                ->line('Escríbenos para reactivar tu cuenta; en cuanto renovemos tu plan, todo vuelve a funcionar igual que antes y no se pierde ningún dato.');
        }

        if ($this->diasRestantes === 0) {
            return $mensaje
                ->subject("Tu plan vence hoy — {$this->company->razon_social}")
                ->line("El plan de **{$this->company->razon_social}** vence hoy, {$fecha}.")
                ->line('A partir de mañana no podrás emitir comprobantes.')
                ->line('Comunícate con nosotros hoy mismo para renovarlo y no interrumpir tu facturación.');
        }

        $dias = $this->diasRestantes === 1 ? '1 día' : "{$this->diasRestantes} días";

        return $mensaje
            ->subject("Tu plan vence en {$dias} — {$this->company->razon_social}")
            ->line("El plan de **{$this->company->razon_social}** vence el {$fecha}, en {$dias}.")
            ->line('Al vencer no podrás emitir comprobantes hasta renovarlo.')
            ->line('Comunícate con nosotros para renovarlo con tiempo.');
    }

    public function toArray(object $notifiable): array
    {
        $vencido = $this->diasRestantes < 0;

        return [
            'tipo' => 'suscripcion',
            'titulo' => $vencido ? 'Plan vencido' : 'Plan por vencer',
            'mensaje' => $vencido
                ? "El plan de {$this->company->razon_social} venció el {$this->fechaVencimiento}. Comunícate con nosotros para reactivarlo."
                : "El plan de {$this->company->razon_social} vence el {$this->fechaVencimiento}. Al vencer no podrás emitir comprobantes.",
            'icono' => $vencido ? '❌' : '⚠️',
            'url' => route('empresa.plan.index'),
        ];
    }
}
