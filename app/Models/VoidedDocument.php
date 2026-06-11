<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoidedDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'correlativo',
        'fecha_generacion',
        'fecha_referencia',
        'motivo',
        'detalles',
        'xml_path',
        'cdr_path',
        'ticket',
        'estado_sunat',
        'respuesta_sunat',
        'usuario_creacion',
    ];

    protected $casts = [
        'fecha_generacion' => 'date',
        'fecha_referencia' => 'date',
        'detalles' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getTipoDocumentoNameAttribute(): string
    {
        return 'Comunicación de Baja';
    }

    public function getEstadoSunatColorAttribute(): string
    {
        return match($this->estado_sunat) {
            'PENDIENTE' => 'warning',
            'ENVIADO' => 'info',
            'ACEPTADO' => 'success',
            'RECHAZADO' => 'danger',
            default => 'secondary'
        };
    }

    public function scopePending($query)
    {
        return $query->where('estado_sunat', 'PENDIENTE');
    }

    public function scopeAccepted($query)
    {
        return $query->where('estado_sunat', 'ACEPTADO');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('fecha_generacion', [$startDate, $endDate]);
    }
}
