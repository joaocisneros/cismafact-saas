<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Una llave de acceso a las consultas de RUC y DNI.
 *
 * Distinta de las de emision: estas puede tenerlas alguien que solo compra
 * consultas. Y un mismo titular quiere varias, una por sistema suyo, para
 * poder cortar una sin dejar sin servicio a las demas.
 */
class ConsultaLlave extends Model
{
    protected $table = 'consulta_llaves';

    protected $fillable = [
        'nombre', 'company_id', 'titular', 'titular_documento', 'titular_email',
        'api_plan_id', 'entorno', 'servicios', 'clave', 'secreto', 'secreto_pista',
        'activa', 'expira_en', 'ultimo_uso_en', 'datos_reales', 'tope_pruebas',
    ];

    protected $casts = [
        'servicios' => 'array',
        'activa' => 'boolean',
        'expira_en' => 'date',
        'ultimo_uso_en' => 'datetime',
        'secreto' => 'encrypted',
        'datos_reales' => 'boolean',
    ];

    protected $hidden = ['secreto'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ApiPlan::class, 'api_plan_id');
    }

    public function consumo(): HasMany
    {
        return $this->hasMany(ConsultaConsumo::class, 'llave_id');
    }

    /** El nombre de quien la usa, venga de donde venga. */
    public function nombreDelTitular(): string
    {
        return $this->empresa?->razon_social ?? $this->titular ?? 'Sin titular';
    }

    /** El tope de esta llave: el suyo si lo tiene, si no el de su plan. */
    public function topeDe(\App\Models\Api $api): int
    {
        return $this->tope_pruebas ?: $api->limiteDelPlan($this->api_plan_id);
    }

    /**
     * El consumo que de verdad gasta cuota, este mes.
     *
     * Lo usan la API para cortar y las tablas para pintar el gasto. Estaba
     * escrito por separado en cada sitio y no decian lo mismo: las tablas
     * contaban tambien las fallidas y lo servido en modo prueba, que no
     * descuentan, asi que enseñaban un consumo mayor del que la API cobraba.
     *
     * Falta acotarlo por llave y por api, que eso cambia segun quien pregunte.
     */
    public static function consumoQueGastaCuota(): \Illuminate\Database\Query\Builder
    {
        return DB::table('consultas_consumo')
            ->where('exito', true)
            // Lo de sandbox se anota para poder verlo, pero no gasta: es lo
            // que se le promete a quien integra.
            ->where('fuente', '!=', 'modo prueba')
            ->where('created_at', '>=', now()->startOfMonth());
    }

    public function vencida(): bool
    {
        return $this->expira_en !== null && $this->expira_en->isPast();
    }

    /**
     * Si puede usarse ahora mismo, y por que no.
     *
     * Se devuelve el motivo y no solo un si/no: al cliente hay que poder
     * decirle si esta bloqueada o si se le caduco, que son cosas distintas y
     * se arreglan distinto.
     */
    public function estado(): string
    {
        if (! $this->activa) {
            return 'bloqueada';
        }

        if ($this->vencida()) {
            return 'vencida';
        }

        return $this->entorno === 'sandbox' ? 'sandbox' : 'activa';
    }

    public function sirve(string $servicio): bool
    {
        return in_array($servicio, (array) $this->servicios, true);
    }

    /** Consultas gastadas este mes. En sandbox no se cuenta ninguna. */
    public function usadasEsteMes(): int
    {
        return $this->consumo()->where('created_at', '>=', now()->startOfMonth())->count();
    }

    public function tope(): int
    {
        return (int) ($this->plan?->apis()->get()->max('pivot.limite_mensual') ?? 0);
    }

    /** Genera clave y secreto. El secreto solo se enseña una vez, al crearla. */
    public static function nuevasCredenciales(): array
    {
        return [
            'clave' => 'ck_' . Str::random(48),
            'secreto' => Str::random(64),
        ];
    }
}
