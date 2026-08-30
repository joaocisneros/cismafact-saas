<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ConsultaDocumentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Consulta de RUC y DNI para los clientes.
 *
 * Dos rutas y no una: lo que devuelve un RUC (estado, condicion, domicilio
 * fiscal) y lo que devuelve un DNI (apellidos) no se parecen. Con una sola,
 * quien la usa tendria que mirar que le llego para saber como leerlo.
 *
 * Entran con la misma clave con la que emiten. No hay que darles nada nuevo.
 */
class ConsultaController extends Controller
{
    public function __construct(private ConsultaDocumentoService $consultas)
    {
    }

    public function ruc(Request $request, string $numero): JsonResponse
    {
        return $this->responder($request, 'ruc', $numero);
    }

    public function dni(Request $request, string $numero): JsonResponse
    {
        return $this->responder($request, 'dni', $numero);
    }

    /** Lo consumido y lo que queda, para que el cliente lo vea sin preguntar. */
    public function cuota(Request $request): JsonResponse
    {
        $empresa = $request->user()->company;
        $tope = (int) ($empresa->plan->consultas_limit ?? 0);
        $usadas = $this->usadasEsteMes($empresa->id);

        return response()->json([
            'plan' => $empresa->plan->name ?? null,
            'limite_mensual' => $tope,
            'usadas' => $usadas,
            'restantes' => max(0, $tope - $usadas),
            'renueva' => now()->startOfMonth()->addMonth()->toDateString(),
        ]);
    }

    private function responder(Request $request, string $tipo, string $numero): JsonResponse
    {
        $empresa = $request->user()->company;
        $tope = (int) ($empresa->plan->consultas_limit ?? 0);
        $usadas = $this->usadasEsteMes($empresa->id);

        if ($tope > 0 && $usadas >= $tope) {
            return response()->json([
                'success' => false,
                'message' => "Has agotado las {$tope} consultas de tu plan este mes.",
                'usadas' => $usadas,
                'limite_mensual' => $tope,
                'renueva' => now()->startOfMonth()->addMonth()->toDateString(),
            ], 429);
        }

        $r = $tipo === 'ruc'
            ? $this->consultas->ruc($numero)
            : $this->consultas->dni($numero);

        // Un numero mal escrito no gasta cuota: el error es de quien pregunta
        // y no ha costado nada resolverlo.
        if ($r['valido']) {
            $this->anotar($empresa->id, $tipo, $numero, $r['fuente'] ?? 'ninguna');
        }

        return response()->json([
            'success' => $r['valido'],
            'data' => $r['valido'] ? $r : null,
            'message' => $r['motivo'] ?? null,
        ], $r['valido'] ? 200 : 422);
    }

    private function usadasEsteMes(int $empresa): int
    {
        return DB::table('consultas_consumo')
            ->where('company_id', $empresa)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    private function anotar(int $empresa, string $tipo, string $numero, string $fuente): void
    {
        DB::table('consultas_consumo')->insert([
            'company_id' => $empresa,
            'tipo' => $tipo,
            'numero' => $numero,
            // De donde salio: sirve para saber cuanto se esta yendo al
            // proveedor de verdad y cuanto se resuelve en casa.
            'fuente' => $fuente,
            'created_at' => now(),
        ]);
    }
}
