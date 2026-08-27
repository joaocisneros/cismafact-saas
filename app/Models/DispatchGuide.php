<?php

namespace App\Models;

use App\Models\Concerns\AisladoPorToken;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchGuide extends Model
{
    /** Dada de baja en el portal SOL de SUNAT y registrada aqui. */
    public function getAnuladoAttribute(): bool
    {
        return $this->anulado_en !== null;
    }

    use HasFactory;
    use AisladoPorToken;

    protected $fillable = [
        'anulado_en', 'anulado_motivo', 'anulado_registrado_por',
        'company_id',
        'api_key_id',   // token de la API con el que se emitio, si vino por ahi
        'branch_id',
        'client_id',
        'tipo_documento',
        'serie',
        'correlativo',
        'numero_completo',
        'fecha_emision',
        'version',
        'cod_traslado',
        'des_traslado',
        'mod_traslado',
        'fecha_traslado',
        'peso_total',
        'und_peso_total',
        'num_bultos',
        'partida',
        'llegada',
        'transportista',
        'indicadores',
        'vehiculo',
        'conductor',
        'detalles',
        'observaciones',
        'xml_path',
        'cdr_path',
        'pdf_path',
        'ticket',
        'estado_sunat',
        'respuesta_sunat',
        'codigo_hash',
        'usuario_creacion',
    ];

    protected $casts = [
        'anulado_en' => 'datetime',
        'fecha_emision' => 'date',
        'fecha_traslado' => 'date',
        'peso_total' => 'decimal:2',
        'partida' => 'array',
        'llegada' => 'array',
        'transportista' => 'array',
        'indicadores' => 'array',
        'vehiculo' => 'array',
        'conductor' => 'array',
        'detalles' => 'array',
        'observaciones' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getTipoDocumentoNameAttribute(): string
    {
        return 'Guía de Remisión Electrónica';
    }

    public function getMotivoTrasladoNameAttribute(): string
    {
        return match($this->cod_traslado) {
            '01' => 'Venta',
            '02' => 'Compra',
            '03' => 'Venta con entrega a terceros',
            '04' => 'Traslado entre establecimientos de la misma empresa',
            '05' => 'Consignacion',
            '06' => 'Devolucion',
            '07' => 'Recojo de bienes transformados',
            '08' => 'Importacion',
            '09' => 'Exportacion',
            '13' => 'Otros',
            '14' => 'Venta sujeta a confirmacion del comprador',
            '17' => 'Traslado de bienes para transformacion',
            '18' => 'Traslado emisor itinerante CP',
            default => $this->des_traslado ?: 'Traslado de bienes',
        };
    }

    public function getModalidadTrasladoNameAttribute(): string
    {
        return match($this->mod_traslado) {
            '01' => 'Transporte publico',
            '02' => 'Transporte privado',
            default => 'No especificada',
        };
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
        
        static::creating(function ($guide) {
            if (empty($guide->numero_completo)) {
                $guide->numero_completo = $guide->serie . '-' . $guide->correlativo;
            }
        });
    }
}
