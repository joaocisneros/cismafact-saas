<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Api;
use App\Models\ConsultaLlave;
use App\Services\ConsultaDocumentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Consulta de RUC y DNI para quien compra el servicio.
 *
 * SIN NADA QUE VER CON LA EMISION. Se entra con una llave de consultas, no con
 * la de facturar: son dos negocios distintos y mezclarlos daria consultas de
 * regalo a quien solo contrato facturacion, y obligaria a tener llave de
 * emision a quien solo quiere consultar.
 *
 * Dos rutas y no una: lo que devuelve un RUC (estado, condicion, domicilio
 * fiscal) y lo que devuelve un DNI (apellidos) no se parecen. Con una sola,
 * quien la usa tendria que mirar que le llego para saber como leerlo.
 *
 * Que servicio existe, si esta encendido y cuanto incluye cada plan sale de la
 * base, no de este archivo: asi se apaga uno sin tocar el otro ni desplegar.
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

    /** Lo consumido y lo que queda, para que lo vea sin preguntar. */
    public function cuota(Request $request): JsonResponse
    {
        $llave = $this->llave($request);

        $servicios = Api::with('planes')
            ->whereIn('slug', (array) $llave->servicios)
            ->get()
            ->map(fn (Api $api) => [
                'servicio' => $api->slug,
                'nombre' => $api->nombre,
                'disponible' => $api->activa,
                'limite_mensual' => $tope = $api->limiteDelPlan($llave->api_plan_id),
                'usadas' => $usadas = $this->usadas($llave->id, $api->id),
                'restantes' => max(0, $tope - $usadas),
            ]);

        return response()->json([
            'llave' => $llave->nombre,
            'entorno' => $llave->entorno,
            'plan' => $llave->plan->nombre ?? null,
            'expira_en' => $llave->expira_en?->toDateString(),
            'renueva' => now()->startOfMonth()->addMonth()->toDateString(),
            'servicios' => $servicios,
        ]);
    }

    private function responder(Request $request, string $slug, string $numero): JsonResponse
    {
        $llave = $this->llave($request);

        // Lo primero: si esta llave da acceso a esto. Antes que nada del
        // servicio, porque es lo que separa "no lo contrataste" de "esta caido".
        if (! $llave->sirve($slug)) {
            return $this->error("Esta llave no da acceso a la consulta de {$slug}.", 403);
        }

        $api = Api::with('planes')->where('slug', $slug)->first();

        if (! $api) {
            return $this->error('Esa consulta no existe.', 404);
        }

        // Apagada a proposito: se corta aqui, sin gastar cuota ni molestar al
        // proveedor. Sirve para cuando el proveedor esta caido, en vez de que
        // cada cliente se coma el error por su cuenta.
        if (! $api->activa) {
            return $this->error("La consulta de {$slug} está temporalmente fuera de servicio.", 503);
        }

        $tope = $api->limiteDelPlan($llave->api_plan_id);

        if ($tope === 0) {
            return $this->error("Tu plan no incluye la consulta de {$slug}.", 403);
        }

        // En sandbox se responde con un ejemplo y no se cuenta: es justo para
        // poder integrar sin gastar lo que se paga.
        //
        // El numero se comprueba igual que en produccion. Si aqui valiera
        // cualquier cosa, quien integra no llegaria a ver nunca un 422 y se lo
        // encontraria de golpe el dia que cambia las credenciales, que es justo
        // lo que este entorno existe para evitar.
        if ($llave->entorno === 'sandbox') {
            if ($motivo = $this->motivoNumeroInvalido($slug, $numero)) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => $motivo,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $api->ejemplo($numero),
                'message' => 'Entorno de pruebas: los datos son de ejemplo y no gastan cuota.',
            ]);
        }

        $usadas = $this->usadas($llave->id, $api->id);

        if ($usadas >= $tope) {
            return response()->json([
                'success' => false,
                'message' => "Has agotado las {$tope} consultas de {$slug} de tu plan este mes.",
                'usadas' => $usadas,
                'limite_mensual' => $tope,
                'renueva' => now()->startOfMonth()->addMonth()->toDateString(),
            ], 429);
        }

        $empezo = microtime(true);

        $r = $slug === 'ruc'
            ? $this->consultas->ruc($numero)
            : $this->consultas->dni($numero);

        // Se anota siempre, salga bien o mal: los errores son justo lo que se
        // busca en un historial. Lo que NO cuenta para la cuota es lo fallido,
        // porque un numero mal escrito no ha costado nada resolverlo.
        $this->anotar(
            $llave,
            $api->id,
            $slug,
            $numero,
            $r['fuente'] ?? 'ninguna',
            (bool) $r['valido'],
            (int) round((microtime(true) - $empezo) * 1000),
            $r['valido'] ? null : ($r['motivo'] ?? null),
        );

        return response()->json([
            'success' => $r['valido'],
            'data' => $r['valido'] ? $r : null,
            'message' => $r['motivo'] ?? null,
        ], $r['valido'] ? 200 : 422);
    }

    private function llave(Request $request): ConsultaLlave
    {
        return $request->attributes->get('llave_consulta');
    }

    /**
     * Por que no vale el numero, o null si vale. Mismas reglas y mismos textos
     * que en produccion, para que el sandbox no de un falso visto bueno.
     */
    private function motivoNumeroInvalido(string $slug, string $numero): ?string
    {
        $limpio = preg_replace('/\D/', '', $numero);

        if ($slug === 'ruc') {
            if (strlen($limpio) !== 11) {
                return 'El RUC debe tener 11 dígitos.';
            }

            return $this->consultas->rucValido($limpio)
                ? null
                : 'El RUC no es válido: el dígito verificador no cuadra.';
        }

        return strlen($limpio) === 8 ? null : 'El DNI debe tener 8 dígitos.';
    }

    /** Lo gastado por esta llave este mes. Solo cuentan las que salieron bien. */
    private function usadas(int $llave, int $api): int
    {
        return DB::table('consultas_consumo')
            ->where('llave_id', $llave)
            ->where('api_id', $api)
            ->where('exito', true)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    private function anotar(
        ConsultaLlave $llave,
        int $api,
        string $tipo,
        string $numero,
        string $fuente,
        bool $exito,
        int $ms,
        ?string $motivo,
    ): void {
        DB::table('consultas_consumo')->insert([
            'llave_id' => $llave->id,
            // Puede ser nulo: una llave de alguien de fuera no cuelga de
            // ninguna empresa del sistema.
            'company_id' => $llave->company_id,
            'api_id' => $api,
            'origen' => 'externo',
            'tipo' => $tipo,
            'numero' => $numero,
            // De donde salio: sirve para saber cuanto se esta yendo al
            // proveedor de verdad y cuanto se resuelve en casa.
            'fuente' => $fuente,
            'exito' => $exito,
            'ms' => min($ms, 65535),
            'motivo' => $motivo ? mb_substr($motivo, 0, 120) : null,
            'created_at' => now(),
        ]);
    }

    private function error(string $mensaje, int $codigo): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $mensaje], $codigo);
    }
}
