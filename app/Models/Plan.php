<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'monthly_document_limit',
        'user_limit',
        'api_request_limit',
        'support_included',
        'monthly_price',
        'active',
    ];

    protected $casts = [
        'monthly_document_limit' => 'integer',
        'user_limit' => 'integer',
        'api_request_limit' => 'integer',
        'monthly_price' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Plan $plan) {
            if (blank($plan->code)) {
                $plan->code = Str::slug($plan->name);
            }
        });
    }
}
