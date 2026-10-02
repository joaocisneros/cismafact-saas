<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionResult extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'period', 'net_sales', 'commission_amount',
        'bonus_amount', 'total_amount', 'status', 'approved_at', 'paid_at',
        'approved_by', 'paid_by',
    ];

    protected $casts = [
        'period' => 'date',
        'net_sales' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
