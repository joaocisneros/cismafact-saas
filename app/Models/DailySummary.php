<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailySummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'correlativo',
        'fecha_generacion',
        'fecha_resumen',
        'ubl_version',
        'moneda',
        'estado_proceso',
        'detalles',
        'estado_sunat',
        'respuesta_sunat',
        'ticket',
        'xml_path',
        'cdr_path',
        'codigo_hash',
        'usuario_creacion',
    ];

    protected $casts = [
        'fecha_generacion' => 'date',
        'fecha_resumen' => 'date',
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

    public function boletas(): HasMany
    {
        return $this->hasMany(Boleta::class);
    }

    public function getTipoDocumentoNameAttribute(): string
    {
        return 'Resumen Diario';
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
        return $query->whereBetween('fecha_resumen', [$startDate, $endDate]);
    }
}
