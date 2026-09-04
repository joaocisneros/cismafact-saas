<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'tipo_documento',
        'numero_documento',
        'razon_social',
        'nombre_comercial',
        'direccion',
        'ubigeo',
        'distrito',
        'provincia',
        'departamento',
        'telefono',
        'email',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /*
     * Los datos se normalizan al guardar, vengan del formulario, del modal o
     * de la API.
     *
     * La razon social y la direccion son las que viajan en el XML a SUNAT. El
     * PDF ya las imprime con strtoupper(), asi que guardarlas en minuscula
     * dejaba el comprobante impreso diciendo una cosa y el XML declarado otra.
     * Se guardan en mayuscula y coinciden los dos.
     *
     * El nombre comercial no va a SUNAT, pero se muestra junto a la razon
     * social: en minuscula la ficha queda a medias.
     */
    protected function razonSocial(): Attribute
    {
        return Attribute::set(fn (?string $v) => $v === null ? null : mb_strtoupper(trim($v), 'UTF-8'));
    }

    protected function nombreComercial(): Attribute
    {
        return Attribute::set(fn (?string $v) => $v === null ? null : mb_strtoupper(trim($v), 'UTF-8'));
    }

    protected function direccion(): Attribute
    {
        return Attribute::set(fn (?string $v) => $v === null ? null : mb_strtoupper(trim($v), 'UTF-8'));
    }

    /*
     * El correo, al reves: en mayuscula "Juan@Gmail.COM" y "juan@gmail.com"
     * son la misma direccion, pero para nosotros serian dos clientes distintos
     * y el control de duplicados no los vería.
     */
    protected function email(): Attribute
    {
        return Attribute::set(fn (?string $v) => $v === null ? null : mb_strtolower(trim($v), 'UTF-8'));
    }

    /* Del telefono solo interesan las cifras: asi se puede buscar. */
    protected function telefono(): Attribute
    {
        return Attribute::set(function (?string $v) {
            if ($v === null) {
                return null;
            }

            $limpio = preg_replace('/[^\d+]/', '', $v);

            return $limpio === '' ? null : $limpio;
        });
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function boletas(): HasMany
    {
        return $this->hasMany(Boleta::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function debitNotes(): HasMany
    {
        return $this->hasMany(DebitNote::class);
    }

    public function dispatchGuides(): HasMany
    {
        return $this->hasMany(DispatchGuide::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getDocumentTypeNameAttribute(): string
    {
        // Del catalogo 06 entero: con la lista corta, un cliente con
        // pasaporte salia en pantalla como «Desconocido».
        return \App\Support\CatalogoSunat::DOCUMENTOS_IDENTIDAD_NOMBRE[$this->tipo_documento] ?? 'Desconocido';
    }

    public function getFullDocumentAttribute(): string
    {
        return $this->tipo_documento . '-' . $this->numero_documento;
    }

    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    public function scopeByDocument($query, string $tipoDocumento, string $numeroDocumento)
    {
        return $query->where('tipo_documento', $tipoDocumento)
                    ->where('numero_documento', $numeroDocumento);
    }
}