<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ConsultaDocumentoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Consultas de RUC y DNI: proveedor, cache y padron.
 *
 * Los tres van juntos porque son el mismo asunto visto de tres maneras: de
 * donde salen los datos, que se ha guardado ya, y la copia local de SUNAT que
 * algun dia hara innecesario al proveedor. Repartirlo en tres entradas del
 * menu solo obligaria a saltar de una a otra para entender el estado.
 */
class ConsultaController extends Controller
{
    public function __construct(private ConsultaDocumentoService $consultas)
    {
    }

    public function index()
    {
        return view('super-admin.consultas.index', [
            'ajustes' => Setting::whereIn('key', ['consultas_url', 'consultas_token'])
                ->pluck('value', 'key')
                ->all(),
            'cache' => $this->resumenCache(),
            'padron' => $this->estadoPadron(),
        ]);
    }

    public function update(Request $request)
    {
        $datos = $request->validate([
            'consultas_url' => 'nullable|url:http,https|max:255',
            'consultas_token' => 'nullable|string|max:255',
        ], [
            'consultas_url.url' => 'La dirección debe empezar por http:// o https://',
        ]);

        foreach ($datos as $clave => $valor) {
            // Un token vacio no borra el que hay: el formulario nunca lo
            // muestra, asi que guardar en blanco seria perderlo sin querer.
            if ($clave === 'consultas_token' && blank($valor)) {
                continue;
            }

            Setting::updateOrCreate(
                ['key' => $clave],
                ['value' => $valor, 'type' => 'text', 'group' => 'consultas'],
            );
        }

        return back()->with('success', 'Configuración de consultas guardada.');
    }

    /** Consulta de prueba, para ver si el proveedor responde. */
    public function probar(Request $request)
    {
        $datos = $request->validate([
            'tipo' => 'required|in:ruc,dni',
            'numero' => 'required|string|max:11',
        ]);

        $resultado = $datos['tipo'] === 'ruc'
            ? $this->consultas->ruc($datos['numero'], usarCache: false)
            : $this->consultas->dni($datos['numero'], usarCache: false);

        return back()->with('consulta_prueba', $resultado);
    }

    public function vaciarCache()
    {
        $cuantas = DB::table('consultas_documento')->count();
        DB::table('consultas_documento')->truncate();

        return back()->with('success', "Se borraron {$cuantas} consultas guardadas.");
    }

    /** @return array<string,mixed> */
    private function resumenCache(): array
    {
        $porTipo = DB::table('consultas_documento')
            ->selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo')
            ->all();

        return [
            'ruc' => $porTipo['ruc'] ?? 0,
            'dni' => $porTipo['dni'] ?? 0,
            'total' => array_sum($porTipo),
            'ultima' => DB::table('consultas_documento')->max('updated_at'),
        ];
    }

    /** @return array<string,mixed> */
    private function estadoPadron(): array
    {
        $ultima = DB::table('padron_importaciones')->latest('id')->first();

        return [
            'filas' => DB::table('padron_ruc')->count(),
            'ultima' => $ultima,
        ];
    }
}
