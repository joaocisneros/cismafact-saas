<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionConfig extends Model
{
    protected $fillable = [
        'company_id', 'active', 'mode', 'monthly_goal', 'commission_percentage',
        'goal_bonus', 'applies_to_all', 'selected_user_ids',
    ];

    protected $casts = [
        'active' => 'boolean',
        'applies_to_all' => 'boolean',
        'selected_user_ids' => 'array',
        'monthly_goal' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'goal_bonus' => 'decimal:2',
    ];
}
