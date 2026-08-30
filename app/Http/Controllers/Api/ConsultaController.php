<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Api;
use App\Services\ConsultaDocumentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Consulta de RUC y DNI para quien compra la API.
 *
 * Dos rutas y no una: lo que devuelve un RUC (estado, condicion, domicilio
 * fiscal) y lo que devuelve un DNI (apellidos) no se parecen. Con una sola,
 * quien la usa tendria que mirar que le llego para saber como leerlo.
 *
 * Que api se sirve, si esta encendida, si esta en pruebas y cuanto incluye
 * cada plan sale de la tabla `apis`, no de este archivo: asi se apaga una sin
 * tocar la otra y sin desplegar nada.
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

    /** Lo consumido y lo que queda de cada api, para que lo vea sin preguntar. */
    public function cuota(Request $request): JsonResponse
    {
        $empresa = $request->user()->company;
        $planId = $empresa->api_plan_id;

        $apis = Api::with('planes')->get()->map(function (Api $api) use ($empresa, $planId) {
            $tope = $api->limiteDelPlan($planId);
            $usadas = $this->usadas($empresa->id, $api->id);

            return [
                'api' => $api->slug,
                'nombre' => $api->nombre,
                'disponible' => $api->activa,
                'modo_prueba' => $api->modo_prueba,
                'limite_mensual' => $tope,
                'usadas' => $usadas,
                'restantes' => max(0, $tope - $usadas),
            ];
        });

        return response()->json([
            'plan' => $empresa->apiPlan->nombre ?? null,
            'renueva' => now()->startOfMonth()->addMonth()->toDateString(),
            'apis' => $apis,
        ]);
    }

    private function responder(Request $request, string $slug, string $numero): JsonResponse
    {
        $api = Api::with('planes')->where('slug', $slug)->first();

        if (! $api) {
            return response()->json(['success' => false, 'message' => 'Esa consulta no existe.'], 404);
        }

        // Apagada a proposito: se corta aqui, sin gastar cuota ni molestar al
        // proveedor. Sirve para cuando el proveedor esta caido, en vez de que
        // cada cliente se coma el error por su cuenta.
        if (! $api->activa) {
            return response()->json([
                'success' => false,
                'message' => "La consulta de {$slug} está temporalmente fuera de servicio.",
            ], 503);
        }

        $empresa = $request->user()->company;
        $tope = $api->limiteDelPlan($empresa->api_plan_id);

        if ($tope === 0) {
            return response()->json([
                'success' => false,
                'message' => "Tu plan no incluye la consulta de {$slug}.",
            ], 403);
        }

        $usadas = $this->usadas($empresa->id, $api->id);

        // En pruebas no se cuenta: es justo para poder integrar sin gastar.
        if (! $api->modo_prueba && $usadas >= $tope) {
            return response()->json([
                'success' => false,
                'message' => "Has agotado las {$tope} consultas de {$slug} de tu plan este mes.",
                'usadas' => $usadas,
                'limite_mensual' => $tope,
                'renueva' => now()->startOfMonth()->addMonth()->toDateString(),
            ], 429);
        }

        if ($api->modo_prueba) {
            return response()->json([
                'success' => true,
                'data' => $api->ejemplo($numero),
                'message' => 'Modo de pruebas: los datos son de ejemplo y no gastan cuota.',
            ]);
        }

        $r = $slug === 'ruc'
            ? $this->consultas->ruc($numero)
            : $this->consultas->dni($numero);

        // Un numero mal escrito no gasta cuota: el error es de quien pregunta
        // y no ha costado nada resolverlo.
        if ($r['valido']) {
            $this->anotar($empresa->id, $api->id, $slug, $numero, $r['fuente'] ?? 'ninguna');
        }

        return response()->json([
            'success' => $r['valido'],
            'data' => $r['valido'] ? $r : null,
            'message' => $r['motivo'] ?? null,
        ], $r['valido'] ? 200 : 422);
    }

    /** Solo lo de fuera cuenta: lo interno ya lo paga el plan de la empresa. */
    private function usadas(int $empresa, int $api): int
    {
        return DB::table('consultas_consumo')
            ->where('company_id', $empresa)
            ->where('api_id', $api)
            ->where('origen', 'externo')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    private function anotar(int $empresa, int $api, string $tipo, string $numero, string $fuente): void
    {
        DB::table('consultas_consumo')->insert([
            'company_id' => $empresa,
            'api_id' => $api,
            'origen' => 'externo',
            'tipo' => $tipo,
            'numero' => $numero,
            // De donde salio: sirve para saber cuanto se esta yendo al
            // proveedor de verdad y cuanto se resuelve en casa.
            'fuente' => $fuente,
            'created_at' => now(),
        ]);
    }
}
