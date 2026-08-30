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

    protected $fillable = ['nombre', 'slug', 'descripcion', 'activa', 'modo_prueba'];

    protected $casts = [
        'activa' => 'boolean',
        'modo_prueba' => 'boolean',
    ];

    public function planes(): BelongsToMany
    {
        return $this->belongsToMany(ApiPlan::class, 'api_plan_limite')
            ->withPivot('limite_mensual')
            ->withTimestamps();
    }

    /** Cuanto incluye un plan de consulta de esta api al mes. 0 = sin acceso. */
    public function limiteDelPlan(?int $planId): int
    {
        if (! $planId) {
            return 0;
        }

        return (int) ($this->planes->firstWhere('id', $planId)?->pivot->limite_mensual ?? 0);
    }

    /**
     * Respuesta de mentira, para el modo pruebas.
     *
     * Deja integrar sin gastar cuota ni salir a internet. Los datos son
     * evidentemente falsos a proposito: si alguien se los queda creyendo que
     * son reales, que se note enseguida.
     */
    public function ejemplo(string $numero): array
    {
        if ($this->slug === 'dni') {
            return [
                'valido' => true,
                'numero' => $numero,
                'tipo' => 'dni',
                'nombre' => 'JUAN DE PRUEBA EJEMPLO',
                'nombres' => 'JUAN',
                'apellido_paterno' => 'DE PRUEBA',
                'apellido_materno' => 'EJEMPLO',
                'fuente' => 'modo prueba',
            ];
        }

        return [
            'valido' => true,
            'numero' => $numero,
            'tipo' => 'ruc',
            'nombre' => 'EMPRESA DE EJEMPLO S.A.C.',
            'estado' => 'ACTIVO',
            'condicion' => 'HABIDO',
            'direccion' => 'AV. DE PRUEBA NRO. 100',
            'ubigeo' => '150101',
            'departamento' => 'LIMA',
            'provincia' => 'LIMA',
            'distrito' => 'LIMA',
            'fuente' => 'modo prueba',
        ];
    }
}
