<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Anota las consultas de RUC y DNI que hace la propia casa desde el panel.
 *
 * Aparte del consumo externo a proposito: aquello lo gastan clientes que pagan
 * y descuenta de su cuota. Esto no se le cobra a nadie, pero cuesta igual —cada
 * consulta que sale al proveedor se paga— y dice que empresa esta tirando mas
 * del servicio.
 *
 * Va en la misma tabla que el externo, distinguido por `origen`, para poder
 * comparar los dos sin cruzar dos sitios distintos.
 */
class ConsumoInterno
{
    public static function anotar(
        ?int $companyId,
        string $tipo,
        string $numero,
        array $resultado,
        int $ms,
    ): void {
        $api = DB::table('apis')->where('slug', $tipo)->value('id');

        DB::table('consultas_consumo')->insert([
            // Sin llave: esto no sale de ninguna que se haya vendido.
            'llave_id' => null,
            'company_id' => $companyId,
            'api_id' => $api,
            'origen' => 'interno',
            'tipo' => $tipo,
            'numero' => $numero,
            // De donde salio el dato: separa lo que costo dinero de lo que se
            // resolvio en casa, que es la mitad de para lo que sirve esto.
            'fuente' => $resultado['fuente'] ?? 'ninguna',
            'exito' => (bool) ($resultado['valido'] ?? false) && ! empty($resultado['nombre']),
            'ms' => min($ms, 65535),
            'motivo' => isset($resultado['motivo']) ? mb_substr($resultado['motivo'], 0, 120) : null,
            'created_at' => now(),
        ]);
    }
}
