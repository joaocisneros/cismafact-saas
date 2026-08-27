<?php

namespace App\Models\Concerns;

use App\Models\ApiKey;
use Illuminate\Database\Eloquent\Builder;

/**
 * Aísla los comprobantes de una empresa sandbox por el token que los emitió.
 *
 * Todos los tokens de prueba cuelgan de la misma empresa demo, porque el
 * certificado de pruebas solo firma con su RUC. Sin aislar, cada programador
 * ve lo que emitieron los demás y no entiende qué hay en pantalla.
 *
 * Hace dos cosas, y solo cuando la petición viene de la API:
 *
 * 1. Al crear, anota el token en el comprobante.
 * 2. Al consultar, y SOLO si la empresa está marcada como demo, esconde lo
 *    emitido con otros tokens.
 *
 * Fuera de la API no se activa: el panel web sigue viendo todo lo de su
 * empresa. Y una empresa real con dos tokens también lo ve todo, porque ahí
 * los comprobantes son suyos y los tokens solo son formas distintas de entrar;
 * el aislamiento es cosa del sandbox, no del cliente de verdad.
 */
trait AisladoPorToken
{
    public static function bootAisladoPorToken(): void
    {
        static::creating(function ($modelo) {
            if ($modelo->api_key_id === null) {
                $modelo->api_key_id = static::tokenDeLaPeticion()?->id;
            }
        });

        static::addGlobalScope('aislado_por_token', function (Builder $query) {
            $token = static::tokenDeLaPeticion();

            if (! $token || ! $token->company?->es_demo) {
                return;
            }

            $query->where($query->getModel()->getTable() . '.api_key_id', $token->id);
        });
    }

    /**
     * El token de la petición en curso, si viene autenticada por API.
     *
     * En consola (comandos, colas, migraciones, tinker) no hay petición que
     * valga: devuelve null y así ni se anota ni se filtra nada.
     */
    protected static function tokenDeLaPeticion(): ?ApiKey
    {
        if (! app()->bound('request')) {
            return null;
        }

        $token = request()->attributes->get('api_key');

        return $token instanceof ApiKey ? $token : null;
    }

    /** Para ver todo lo de la empresa, sin mirar de qué token salió. */
    public function scopeDeCualquierToken(Builder $query): Builder
    {
        return $query->withoutGlobalScope('aislado_por_token');
    }
}
