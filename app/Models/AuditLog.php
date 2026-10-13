<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'actor_user_id',
        'actor_name',
        'actor_email',
        'target_email',
        'target_name',
        'company_id',
        'action',
        'method',
        'route_name',
        'path',
        'subject_type',
        'subject_id',
        'description',
        'service_context',
        'environment_context',
        'event_type',
        'data',
        'ip_address',
        'user_agent',
        'response_status',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Persona real que ejecutó la acción, incluso durante soporte. */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
