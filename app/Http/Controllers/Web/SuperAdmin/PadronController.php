<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ConsultaDocumentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Padron reducido de SUNAT: estado y actualizacion.
 *
 * La importacion NO corre aqui dentro. Son millones de filas y horas de
 * proceso: la peticion caducaria a mitad y dejaria la tabla nueva colgada.
 * Lo que hace el boton es arrancar `padron:actualizar` como proceso suelto y
 * volver enseguida; el progreso se sigue mirando padron_importaciones, que el
 * comando va escribiendo.
 */
class PadronController extends Controller
{
    public function index()
    {
        return view('super-admin.padron.index', [
            'filas' => DB::table('padron_ruc')->count(),
            'importaciones' => DB::table('padron_importaciones')->latest('id')->limit(5)->get(),
            'enMarcha' => $this->enMarcha(),
            'espacio' => $this->espacio(),
            'puedeLanzar' => $this->puedeLanzar(),
            // El proveedor externo vive aqui, junto al padron: las dos son
            // fuentes del mismo dato, y en la pantalla de la API estorbaba
            // porque alli se mira lo que se vende, no de donde sale.
            'ajustes' => Setting::whereIn('key', ['consultas_url', 'consultas_token'])
                ->pluck('value', 'key')
                ->all(),
        ]);
    }

    public function probar(Request $request, ConsultaDocumentoService $consultas)
    {
        $datos = $request->validate([
            'tipo' => 'required|in:ruc,dni',
            'numero' => 'required|string|max:11',
        ]);

        return back()->with('consulta_prueba', $datos['tipo'] === 'ruc'
            ? $consultas->ruc($datos['numero'], usarCache: false)
            : $consultas->dni($datos['numero'], usarCache: false));
    }

    public function actualizar()
    {
        if ($this->enMarcha()) {
            return back()->with('error', 'Ya hay una actualización en marcha.');
        }

        if (! $this->puedeLanzar()) {
            return back()->with('error',
                'Este servidor no deja arrancar procesos, así que hay que lanzarlo a mano: '
                . 'php artisan padron:actualizar');
        }

        $php = PHP_BINARY;
        $raiz = base_path();

        // Suelto y sin esperarlo: la peticion tiene que volver ya.
        $orden = PHP_OS_FAMILY === 'Windows'
            ? "start /B \"\" \"{$php}\" artisan padron:actualizar"
            : "{$php} artisan padron:actualizar > /dev/null 2>&1 &";

        $proceso = @proc_open($orden, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $canales, $raiz);

        if (is_resource($proceso)) {
            foreach ($canales as $c) {
                fclose($c);
            }
            proc_close($proceso);
        }

        return back()->with('success',
            'Actualización lanzada. Tarda un buen rato: esta página va mostrando cómo avanza.');
    }

    /** Para que la pantalla se refresque sola sin recargarla entera. */
    public function estado(): JsonResponse
    {
        $ultima = DB::table('padron_importaciones')->latest('id')->first();

        return response()->json([
            'filas' => DB::table('padron_ruc')->count(),
            'en_marcha' => $this->enMarcha(),
            'estado' => $ultima->estado ?? null,
            'importadas' => (int) ($ultima->filas ?? 0),
            'mensaje' => $ultima->mensaje ?? '',
        ]);
    }

    private function enMarcha(): bool
    {
        return DB::table('padron_importaciones')
            ->whereIn('estado', ['descargando', 'importando'])
            // Una importacion que lleva mas de seis horas sin terminar es un
            // proceso muerto, no una en marcha: si no, el boton no vuelve.
            ->where('updated_at', '>=', now()->subHours(6))
            ->exists();
    }

    private function puedeLanzar(): bool
    {
        $desactivadas = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return function_exists('proc_open') && ! in_array('proc_open', $desactivadas, true);
    }

    /** @return array{libre: float|null, necesario: int} En GB. */
    private function espacio(): array
    {
        $libre = @disk_free_space(base_path());

        return [
            'libre' => $libre ? round($libre / 1024 ** 3, 1) : null,
            'necesario' => 3,
        ];
    }
}
