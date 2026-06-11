<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiUsage extends Model
{
    use HasFactory;

    protected $table = 'api_usage';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'api_key_id',
        'method',
        'path',
        'status_code',
        'ip_address',
        'user_agent',
        'request_body',
        'response_time_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'request_body' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }
}
