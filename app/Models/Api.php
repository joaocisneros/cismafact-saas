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

    /**
     * Respuesta de mentira, para el modo pruebas.
     *
     * Deja integrar sin gastar cuota ni salir a internet. Los datos son
     * evidentemente falsos a proposito: si alguien se los queda creyendo que
     * son reales, que se note enseguida.
     */
    public function ejemplo(string $numero): array
    {
        // Una cifra del numero elige el caso. Antes se devolvia siempre la
        // misma ficha, y con eso solo se puede probar que la peticion llega:
        // no hay forma de comprobar que pasa cuando el RUC esta de baja o no
        // habido, que es justo donde suele fallar la integracion del cliente.
        //
        // Se elige por el numero y no al azar para que las pruebas den siempre
        // lo mismo: una que falla tiene que poder repetirse.
        //
        // En el RUC es la PENULTIMA: la ultima es el digito verificador y no se
        // puede elegir, va calculada a partir de las demas. El DNI no lleva
        // verificador, asi que ahi vale la ultima.
        if ($this->slug === 'dni') {
            return $this->ejemploDni($numero, (int) substr($numero, -1));
        }

        return $this->ejemploRuc($numero, (int) substr($numero, -2, 1));
    }

    /** @return array<string,mixed> */
    private function ejemploRuc(string $numero, int $caso): array
    {
        $casos = [
            // Termina en 1: el caso corriente, activo y habido.
            1 => ['EMPRESA DE EJEMPLO S.A.C.', 'ACTIVO', 'HABIDO'],
            // En 2: de baja. El cliente no deberia dejar facturarle.
            2 => ['EJEMPLO CERRADO S.R.L.', 'BAJA DE OFICIO', 'NO HABIDO'],
            // En 3: activa pero no habida, que se puede facturar con reparos.
            3 => ['EJEMPLO MUDADO E.I.R.L.', 'ACTIVO', 'NO HABIDO'],
            // En 4: suspension temporal.
            4 => ['EJEMPLO EN PAUSA S.A.', 'SUSPENSION TEMPORAL', 'HABIDO'],
            // En 5: nombre largo, para ver si le cabe en pantalla.
            5 => ['CORPORACION DE SERVICIOS GENERALES Y REPRESENTACIONES DEL PERU SOCIEDAD ANONIMA CERRADA', 'ACTIVO', 'HABIDO'],
        ];

        [$nombre, $estado, $condicion] = $casos[$caso] ?? $casos[1];

        return [
            'valido' => true,
            'numero' => $numero,
            'tipo' => 'ruc',
            'nombre' => $nombre,
            'estado' => $estado,
            'condicion' => $condicion,
            'direccion' => 'AV. DE PRUEBA NRO. ' . (100 + $caso),
            'ubigeo' => '150101',
            'departamento' => 'LIMA',
            'provincia' => 'LIMA',
            'distrito' => 'LIMA',
            'fuente' => 'modo prueba',
        ];
    }

    /** @return array<string,mixed> */
    private function ejemploDni(string $numero, int $caso): array
    {
        $casos = [
            1 => ['JUAN', 'DE PRUEBA', 'EJEMPLO'],
            // Sin apellido materno: pasa, y parte los nombres mal armados.
            2 => ['MARIA', 'EJEMPLO', ''],
            // Nombre compuesto y apellidos largos.
            3 => ['JOSE LUIS', 'DE LA CRUZ', 'VASQUEZ DE PRUEBA'],
            // Con tilde y con enye, que es donde se ven los problemas de acentos.
            4 => ['ANDRÉS', 'MUÑOZ', 'PEÑA'],
        ];

        [$nombres, $paterno, $materno] = $casos[$caso] ?? $casos[1];

        return [
            'valido' => true,
            'numero' => $numero,
            'tipo' => 'dni',
            'nombre' => trim($nombres . ' ' . $paterno . ' ' . $materno),
            'nombres' => $nombres,
            'apellido_paterno' => $paterno,
            'apellido_materno' => $materno,
            'fuente' => 'modo prueba',
        ];
    }
}
