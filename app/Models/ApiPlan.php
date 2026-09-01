<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Un plan de las consultas de RUC y DNI.
 *
 * Aparte de los planes de facturacion: quien compra la consulta no
 * necesariamente factura con el sistema.
 */
class ApiPlan extends Model
{
    protected $table = 'api_planes';

    protected $fillable = ['nombre', 'slug', 'descripcion', 'precio_mensual', 'a_medida', 'activo', 'orden'];

    protected $casts = [
        'precio_mensual' => 'decimal:2',
        'a_medida' => 'boolean',
        'activo' => 'boolean',
    ];

    public function apis(): BelongsToMany
    {
        return $this->belongsToMany(Api::class, 'api_plan_limite')
            ->withPivot('limite_mensual')
            ->withTimestamps();
    }

    /** El de las llaves de prueba: no se cobra y no es a convenir. */
    public function esGratis(): bool
    {
        return ! $this->a_medida && (float) $this->precio_mensual <= 0;
    }

    /**
     * El plan con el que salen las llaves de sandbox.
     *
     * Se busca por precio, no por posicion en la lista: antes se cogia el
     * primero del desplegable y ese orden depende de como esten los precios,
     * asi que crear otro plan barato habria cambiado de plan a las llaves de
     * prueba sin que nadie lo pidiera.
     */
    public static function gratis(): ?self
    {
        return static::where('a_medida', false)
            ->where('precio_mensual', '<=', 0)
            ->orderBy('orden')
            ->first();
    }

    /** Lo que se enseña donde iria el importe. */
    public function precio(): string
    {
        return $this->a_medida ? 'Personalizado' : 'S/ ' . number_format((float) $this->precio_mensual, 2);
    }
}
