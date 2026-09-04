<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ConsultaDocumentoService;
use App\Services\PadronSunatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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
            'empezo' => optional(DB::table('padron_importaciones')->latest('id')->first())->iniciada_en,
            'referencia' => $this->referencia(),
            'enSunat' => $this->disponibleEnSunat(),
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
            'numero' => ['required', $request->input('tipo') === 'dni' ? 'digits:8' : 'digits:11'],
        ], [
            'numero.digits' => $request->input('tipo') === 'dni'
                ? 'El DNI son 8 dígitos.'
                : 'El RUC son 11 dígitos.',
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
            // Para contar el tiempo que lleva: en una tarea de horas es lo que
            // dice que sigue viva.
            'empezo' => $ultima?->iniciada_en
                ? \Illuminate\Support\Carbon::parse($ultima->iniciada_en)->timestamp
                : null,
            'referencia' => $this->referencia(),
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
    /**
     * Contra cuantos RUC se mide el avance.
     *
     * La importacion no sabe cuantos va a traer hasta que termina, asi que sin
     * una referencia no hay porcentaje que enseñar. La mejor es lo que trajo la
     * ultima vez que fue bien: el padron crece poco de un mes a otro. La
     * primera vez no hay ninguna y se usa el tamaño habitual del padron, y ahi
     * el porcentaje se enseña como aproximado.
     *
     * @return array{filas: int, exacta: bool}
     */
    /**
     * Que padron tiene SUNAT publicado ahora mismo.
     *
     * Solo pide las cabeceras: no descarga el archivo. Con eso se sabe de que
     * dia es y cuanto pesa, que es lo que hace falta para decidir si merece la
     * pena gastar seis horas.
     *
     * Se guarda una hora: la pantalla se abre muchas veces y SUNAT publica
     * como mucho un archivo al dia.
     *
     * @return array{fecha: string|null, bytes: int|null, error: string|null}
     */
    private function disponibleEnSunat(): array
    {
        return Cache::remember('padron_disponible', now()->addHour(), function () {
            try {
                $r = Http::withHeaders(PadronSunatService::cabeceras())
                    ->connectTimeout(4)
                    ->timeout(8)
                    ->head(PadronSunatService::urlDelArchivo());

                if (! $r->successful()) {
                    return ['fecha' => null, 'bytes' => null, 'error' => 'SUNAT respondió ' . $r->status() . '.'];
                }

                $publicado = $r->header('Last-Modified');

                return [
                    'fecha' => $publicado ? Carbon::parse($publicado)->toDateString() : null,
                    'bytes' => (int) $r->header('Content-Length') ?: null,
                    'error' => null,
                ];
            } catch (\Throwable $e) {
                // Que no responda no es motivo para romper la pantalla: se
                // deja de enseñar el aviso y ya.
                return ['fecha' => null, 'bytes' => null, 'error' => 'No se pudo preguntar a SUNAT.'];
            }
        });
    }

    private function referencia(): array
    {
        $ultimaBuena = (int) DB::table('padron_importaciones')
            ->where('estado', 'completada')
            ->max('filas');

        return $ultimaBuena > 0
            ? ['filas' => $ultimaBuena, 'exacta' => true]
            : ['filas' => 11_500_000, 'exacta' => false];
    }

    private function espacio(): array
    {
        $libre = @disk_free_space(base_path());

        return [
            'libre' => $libre ? round($libre / 1024 ** 3, 1) : null,
            'necesario' => 3,
        ];
    }
}
