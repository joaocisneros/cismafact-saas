<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * La API de RUC y DNI que se sirve a los clientes.
 *
 * Lo primero de la pantalla es el consumo, porque es lo que se cobra y lo que
 * hay que vigilar. El proveedor va al final: es fontaneria, y ademas deja de
 * hacer falta el dia que se importe el padron.
 */
class ConsultaController extends Controller
{
    public function index()
    {
        return view('super-admin.consultas.index', [
            'ajustes' => Setting::whereIn('key', ['consultas_url', 'consultas_token'])
                ->pluck('value', 'key')
                ->all(),
            'cabecera' => $this->cabecera(),
            'mes' => $this->consumoDelMes(),
            'empresas' => $this->porEmpresa(),
            'guardadas' => $this->guardadas(),
            'padron' => DB::table('padron_ruc')->count(),
            // Las cuotas se editan aqui y no en Planes: quien mira el consumo
            // es quien decide el tope, y mandarle a otro modulo para cambiar un
            // numero que esta viendo solo obliga a ir y volver.
            // Cada api con sus planes y lo que incluye en cada uno: es lo que
            // se enseña en la pestaña, una tarjeta por escalon.
            'apis' => \App\Models\Api::with(['planes' => fn ($q) => $q->orderBy('orden')])->get(),
            'planesApi' => \App\Models\ApiPlan::orderBy('orden')->get(),
        ]);
    }

    /** Lo que incluye cada plan de cada api. */
    public function cuotas(Request $request)
    {
        $datos = $request->validate([
            'cuotas' => 'required|array',
            'cuotas.*' => 'required|array',
            'cuotas.*.*' => 'required|integer|min:0|max:10000000',
        ], [
            'cuotas.*.*.integer' => 'Las cuotas se escriben en números enteros.',
            'cuotas.*.*.min' => 'Una cuota no puede ser negativa. Escribe 0 para dejar ese plan sin acceso.',
        ]);

        foreach ($datos['cuotas'] as $api => $porPlan) {
            foreach ($porPlan as $plan => $tope) {
                DB::table('api_plan_limite')
                    ->where('api_id', $api)
                    ->where('api_plan_id', $plan)
                    ->update(['limite_mensual' => (int) $tope, 'updated_at' => now()]);
            }
        }

        return back()->with('success', 'Cuotas actualizadas.');
    }

    /**
     * Las cifras de cabecera.
     *
     * Cosas que sirven para decidir algo, no detalles de como esta hecho por
     * dentro: cuanto se usa, si crece, quien lo usa, y a quien hay que llamar
     * porque se le acaba la cuota.
     *
     * @return array<string,int>
     */
    private function cabecera(): array
    {
        $mes = now()->startOfMonth();

        $cercaDelTope = DB::table('consultas_consumo')
            ->join('companies', 'companies.id', '=', 'consultas_consumo.company_id')
            ->join('plans', 'plans.id', '=', 'companies.plan_id')
            ->where('consultas_consumo.created_at', '>=', $mes)
            ->where('plans.consultas_limit', '>', 0)
            // El select explicito no es adorno: con GROUP BY, MySQL rechaza el
            // "select *" que pone Laravel por defecto (only_full_group_by).
            ->select('companies.id')
            ->groupBy('companies.id', 'plans.consultas_limit')
            ->havingRaw('COUNT(*) >= plans.consultas_limit * 0.8')
            ->get()
            ->count();

        return [
            'mes' => DB::table('consultas_consumo')->where('created_at', '>=', $mes)->count(),
            'hoy' => DB::table('consultas_consumo')->where('created_at', '>=', now()->startOfDay())->count(),
            'empresas' => DB::table('consultas_consumo')
                ->where('created_at', '>=', $mes)
                ->distinct()
                ->count('company_id'),
            'cerca_del_tope' => $cercaDelTope,
        ];
    }

    /**
     * Cuantas consultas se sirvieron este mes y de donde salieron.
     *
     * La diferencia entre lo servido y lo que se fue al proveedor es lo que
     * ahorra tener las fichas en casa: eso es lo que interesa mirar.
     *
     * @return array<string,int>
     */
    private function consumoDelMes(): array
    {
        $filas = DB::table('consultas_consumo')
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('tipo, fuente, COUNT(*) as total')
            ->groupBy('tipo', 'fuente')
            ->get();

        $enCasa = ['padron', 'consultado antes'];

        return [
            'total' => (int) $filas->sum('total'),
            'ruc' => (int) $filas->where('tipo', 'ruc')->sum('total'),
            'dni' => (int) $filas->where('tipo', 'dni')->sum('total'),
            'en_casa' => (int) $filas->whereIn('fuente', $enCasa)->sum('total'),
            'al_proveedor' => (int) $filas->where('fuente', 'proveedor')->sum('total'),
        ];
    }

    /** Quien consulta y cuanto le queda, ordenado por quien mas gasta. */
    private function porEmpresa()
    {
        return DB::table('consultas_consumo')
            ->join('companies', 'companies.id', '=', 'consultas_consumo.company_id')
            ->leftJoin('plans', 'plans.id', '=', 'companies.plan_id')
            ->where('consultas_consumo.created_at', '>=', now()->startOfMonth())
            ->groupBy('companies.id', 'companies.razon_social', 'companies.ruc', 'plans.name', 'plans.consultas_limit')
            ->selectRaw('companies.id, companies.razon_social, companies.ruc, plans.name as plan,
                         plans.consultas_limit as tope, COUNT(*) as usadas,
                         SUM(consultas_consumo.fuente = ?) as al_proveedor', ['proveedor'])
            ->orderByDesc('usadas')
            ->limit(20)
            ->get();
    }

    /** @return array<string,int> */
    private function guardadas(): array
    {
        $porTipo = DB::table('consultas_documento')
            ->selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo')
            ->all();

        return [
            'ruc' => (int) ($porTipo['ruc'] ?? 0),
            'dni' => (int) ($porTipo['dni'] ?? 0),
            'total' => (int) array_sum($porTipo),
        ];
    }
}
