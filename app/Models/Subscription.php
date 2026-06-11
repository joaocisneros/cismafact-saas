<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'next_billing_at',
        'monthly_price',
        'auto_renew',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'next_billing_at' => 'date',
        'monthly_price' => 'decimal:2',
        'auto_renew' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function allowsAccess(): bool
    {
        return in_array($this->status, ['trial', 'active'], true)
            && $this->starts_at->lte(today())
            && (! $this->ends_at || $this->ends_at->gte(today()));
    }
}
