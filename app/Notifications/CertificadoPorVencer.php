<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CertificadoPorVencer extends Notification
{
    use Queueable;

    public function __construct(private Company $company, private int $diasRestantes)
    {
    }

    /** Canal: base de datos (se muestra dentro del sistema). */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $vencido = $this->diasRestantes < 0;

        return [
            'tipo' => 'certificado',
            'titulo' => $vencido ? 'Certificado vencido' : 'Certificado por vencer',
            'mensaje' => $vencido
                ? "El certificado digital de {$this->company->razon_social} venció. No podrás emitir hasta renovarlo."
                : "El certificado digital de {$this->company->razon_social} vence en {$this->diasRestantes} día(s). Renuévalo para no dejar de emitir.",
            'icono' => $vencido ? '❌' : '⚠️',
            'url' => route('empresa.sunat-config.index'),
        ];
    }
}
