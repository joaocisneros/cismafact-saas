<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Una API de las que se ofrecen a los clientes.
 *
 * Existe como fila y no como ruta escrita a mano para poder apagarla sin
 * tocar la otra, cobrarlas distinto o dejar una en pruebas mientras se ajusta.
 */
class Api extends Model
{
    protected $table = 'apis';

    protected $fillable = ['nombre', 'slug', 'descripcion', 'activa'];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function planes(): BelongsToMany
    {
        return $this->belongsToMany(ApiPlan::class, 'api_plan_limite')
            ->withPivot('limite_mensual', 'precio_mensual')
            ->withTimestamps();
    }

    public function consumo()
    {
        return $this->hasMany(\App\Models\ConsultaConsumo::class, 'api_id');
    }

    /** Cuanto incluye un plan de consulta de esta api al mes. 0 = sin acceso. */
    public function limiteDelPlan(?int $planId): int
    {
        if (! $planId) {
            return 0;
        }

        return (int) ($this->planes->firstWhere('id', $planId)?->pivot->limite_mensual ?? 0);
    }

}
