<?php

namespace App\Http\Controllers\Web\Consultas;

use App\Http\Controllers\Controller;
use App\Models\Api;
use App\Models\ConsultaLlave;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * El panel del titular de una llave de RUC y DNI.
 *
 * Ve su llave, su consumo y sus consultas. Nada mas del sistema: ni empresas,
 * ni comprobantes, ni las llaves de otros. Antes, para saber cuanta cuota le
 * quedaba tenia que escribir y esperar a que alguien mirase.
 *
 * Todo lo que se consulta aqui sale de {@see llavesDelUsuario()}, que filtra
 * por el usuario conectado. No se acepta ningun id que venga de la peticion:
 * asi no hay forma de pedir la llave de otro cambiando un numero.
 */
class PanelController extends Controller
{
    public function panel()
    {
        $llaves = $this->llavesDelUsuario();
        $consumo = $this->consumoDelMes($llaves);

        return view('consultas.panel', [
            'llaves' => $llaves,
            'consumo' => $consumo,
            'gastadas' => array_sum(array_column($consumo, 'usadas')),
            'disponibles' => array_sum(array_column($consumo, 'tope')),
            'ultimas' => $this->ultimasConsultas($llaves, 5),
        ]);
    }

    public function credenciales()
    {
        return view('consultas.credenciales', [
            'llaves' => $this->llavesDelUsuario(),
        ]);
    }

    public function consumo()
    {
        $llaves = $this->llavesDelUsuario();

        return view('consultas.consumo', [
            'llaves' => $llaves,
            'consumo' => $this->consumoDelMes($llaves),
            'meses' => $this->ultimosMeses($llaves),
        ]);
    }

    public function consultas(Request $request)
    {
        $llaves = $this->llavesDelUsuario();

        $filas = DB::table('consultas_consumo')
            ->whereIn('llave_id', $llaves->pluck('id'))
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->string('tipo')))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('desde')))
            ->orderByDesc('created_at')
            ->paginate(30, ['created_at', 'tipo', 'numero', 'exito', 'fuente'])
            ->withQueryString();

        return view('consultas.consultas', [
            'consultas' => $filas,
            'gastadas' => $this->gastadasEsteMes($llaves),
        ]);
    }

    public function documentacion()
    {
        return view('consultas.documentacion', [
            'llave' => $this->llavesDelUsuario()->first(),
        ]);
    }

    /**
     * Ya no hay pantalla de cuenta.
     *
     * Los datos y la contraseña se cambian en los modales del avatar, que
     * estan en todas las pantallas: no son un modulo del servicio, son de
     * quien entra. Esta ruta queda para quien llegue por la direccion vieja.
     */
    public function cuenta()
    {
        return redirect()->route('consultas.panel');
    }

    public function guardarCuenta(Request $request)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,' . Auth::id()],
        ]);

        Auth::user()->update($datos);

        return back()->with('success', 'Tus datos quedaron guardados.');
    }

    public function cambiarClave(Request $request)
    {
        $datos = $request->validate([
            'actual' => ['required'],
            'nueva' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nueva.min' => 'La contraseña nueva necesita al menos 8 caracteres.',
            'nueva.confirmed' => 'Las dos contraseñas nuevas no coinciden.',
        ]);

        if (! Hash::check($datos['actual'], Auth::user()->password)) {
            return back()->withErrors(['actual' => 'Esa no es tu contraseña actual.']);
        }

        Auth::user()->update(['password' => Hash::make($datos['nueva'])]);

        return back()->with('success', 'Contraseña cambiada.');
    }

    /**
     * Genera un secreto nuevo para una llave suya.
     *
     * La llave se busca entre las del usuario, no por el id que llega: pedir
     * el id de otro devuelve 404, no el secreto de un desconocido.
     */
    public function regenerarSecreto(int $llave)
    {
        $suya = $this->llavesDelUsuario()->firstWhere('id', $llave);

        abort_unless($suya, 404);

        $secreto = $suya->regenerarSecreto();

        // Se guarda el id, no solo el nombre: la pantalla lo usa para destapar
        // el campo de esa llave y no de otra.
        return back()->with('secreto_nuevo', $suya->id);
    }

    // -----------------------------------------------------------------
    // Lo que sostiene todo lo de arriba
    // -----------------------------------------------------------------

    /** Las llaves de quien esta conectado, y solo esas. */
    private function llavesDelUsuario()
    {
        return ConsultaLlave::where('usuario_id', Auth::id())
            ->with('plan')
            ->orderByDesc('entorno')
            ->get();
    }

    /**
     * Lo gastado este mes por servicio, con su tope.
     *
     * El tope es el de la llave y no el del plan: una de prueba lleva el suyo,
     * y ensenar el del plan prometia consultas que no tiene.
     */
    private function consumoDelMes($llaves): array
    {
        if ($llaves->isEmpty()) {
            return [];
        }

        $servicios = collect($llaves->pluck('servicios')->flatten())->unique()->values();
        $salida = [];

        foreach (Api::whereIn('slug', $servicios)->get() as $api) {
            $tope = $llaves->sum(fn ($llave) => $llave->topeDe($api));

            $usadas = DB::table('consultas_consumo')
                ->whereIn('llave_id', $llaves->pluck('id'))
                ->where('api_id', $api->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->where('fuente', '!=', 'modo prueba')
                ->count();

            $salida[] = [
                'slug' => $api->slug,
                'nombre' => $api->nombre,
                'usadas' => $usadas,
                'tope' => $tope,
                'restantes' => max(0, $tope - $usadas),
                'porcentaje' => $tope > 0 ? min(100, round($usadas / $tope * 100, 1)) : 0,
            ];
        }

        return $salida;
    }

    /** Cuantas consultas de las que gastan lleva este mes. */
    private function gastadasEsteMes($llaves): int
    {
        return DB::table('consultas_consumo')
            ->whereIn('llave_id', $llaves->pluck('id'))
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('fuente', '!=', 'modo prueba')
            ->count();
    }

    /** Las ultimas consultas suyas, para la tabla del panel. */
    private function ultimasConsultas($llaves, int $cuantas)
    {
        if ($llaves->isEmpty()) {
            return collect();
        }

        return DB::table('consultas_consumo')
            ->whereIn('llave_id', $llaves->pluck('id'))
            ->orderByDesc('created_at')
            ->limit($cuantas)
            ->get(['created_at', 'tipo', 'numero', 'exito', 'fuente']);
    }

    /** Seis meses de consumo, para la grafica. */
    private function ultimosMeses($llaves): array
    {
        if ($llaves->isEmpty()) {
            return [];
        }

        $desde = now()->subMonths(5)->startOfMonth();

        $porMes = DB::table('consultas_consumo')
            ->whereIn('llave_id', $llaves->pluck('id'))
            ->where('created_at', '>=', $desde)
            ->where('fuente', '!=', 'modo prueba')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes, COUNT(*) as total")
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $salida = [];

        for ($i = 5; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $salida[] = [
                'etiqueta' => Carbon::parse($fecha)->translatedFormat('M'),
                'total' => (int) ($porMes[$fecha->format('Y-m')] ?? 0),
            ];
        }

        return $salida;
    }
}
