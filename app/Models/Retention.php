<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Retention extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'proveedor_tipo_doc',
        'proveedor_num_doc',
        'proveedor_razon_social',
        'serie',
        'correlativo',
        'numero_completo',
        'fecha_emision',
        'regimen',
        'tasa',
        'observacion',
        'imp_retenido',
        'imp_pagado',
        'moneda',
        'detalles',
        'xml_path',
        'cdr_path',
        'pdf_path',
        'estado_sunat',
        'respuesta_sunat',
        'codigo_hash',
        'usuario_creacion',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'tasa' => 'decimal:2',
        'imp_retenido' => 'decimal:2',
        'imp_pagado' => 'decimal:2',
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
        return 'Comprobante de Retención Electrónico';
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
        return $query->whereBetween('fecha_emision', [$startDate, $endDate]);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($retention) {
            if (empty($retention->numero_completo)) {
                $retention->numero_completo = $retention->serie . '-' . $retention->correlativo;
            }
        });
    }
}
