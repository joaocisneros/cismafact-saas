<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'key',
        'secret',
        'abilities',
        'active',
        'last_used_at',
        'expires_at',
        'metadata',
    ];

    protected $hidden = [
        'secret',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'active' => 'boolean',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function usages()
    {
        return $this->hasMany(ApiUsage::class);
    }

    public function getMaskedKeyAttribute(): string
    {
        $key = $this->key;
        return substr($key, 0, 8) . '...' . substr($key, -8);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public static function generateKey(): string
    {
        return 'cf_' . Str::random(60);
    }

    public static function generateSecret(): string
    {
        return Str::random(64);
    }

    public function recordUsage(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
