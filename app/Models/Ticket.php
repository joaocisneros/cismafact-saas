<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    /** De que va el ticket. Determina quien lo atiende y con que urgencia. */
    public const MOTIVOS = [
        'soporte' => 'Soporte técnico',
        'renovacion' => 'Renovación de plan',
        'cambio_plan' => 'Cambio de plan',
        'consulta' => 'Consulta general',
    ];

    /**
     * Prioridad segun el motivo, sin preguntarle al cliente.
     *
     * Pedirsela no funcionaba: todo el mundo marca "alta" porque su problema le
     * urge, y la columna dejaba de ordenar nada. El motivo si es objetivo: un
     * fallo tecnico le impide facturar, una renovacion corre pero no le para el
     * negocio, y una duda espera.
     *
     * El Super Admin puede cambiarla despues desde su bandeja, que es quien ve
     * el caso completo.
     */
    public const PRIORIDAD_POR_MOTIVO = [
        'soporte' => 'high',
        'renovacion' => 'medium',
        'cambio_plan' => 'medium',
        'consulta' => 'low',
    ];

    public static function prioridadSegunMotivo(?string $motivo): string
    {
        return self::PRIORIDAD_POR_MOTIVO[$motivo] ?? 'medium';
    }

    public function getMotivoNombreAttribute(): string
    {
        return self::MOTIVOS[$this->motivo] ?? 'Soporte técnico';
    }

    protected $fillable = ['user_id', 'company_id', 'motivo', 'subject', 'message', 'status', 'priority'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class)->oldest('created_at');
    }
}
