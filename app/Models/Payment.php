<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const METODOS = [
        'efectivo' => 'Efectivo',
        'transferencia' => 'Transferencia bancaria',
        'yape' => 'Yape',
        'plin' => 'Plin',
        'tarjeta' => 'Tarjeta',
        'otro' => 'Otro',
    ];

    public const ESTADOS = [
        'pending' => 'Pendiente',
        'confirmed' => 'Confirmado',
        'refunded' => 'Reembolsado',
    ];

    protected $fillable = [
        'company_id',
        'subscription_id',
        'plan_id',
        'amount',
        'currency',
        'method',
        'reference',
        'paid_at',
        'months',
        'status',
        'notes',
        'registered_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
        'months' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function metodoLabel(): string
    {
        return self::METODOS[$this->method] ?? $this->method;
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->status] ?? $this->status;
    }
}
