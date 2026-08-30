<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Api;
use App\Models\ApiPlan;
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
            // Lo gastado por cada llave este mes. Se llamaba 'empresas' y
            // chocaba con la lista de empresas de mas abajo: la segunda pisaba
            // a la primera y la pestaña de consumo se quedaba sin datos.
            'consumo' => $this->porLlave(),
            'guardadas' => $this->guardadas(),
            'padron' => DB::table('padron_ruc')->count(),
            'apis' => Api::with(['planes' => fn ($q) => $q->orderBy('precio_mensual')->orderBy('orden')])
                ->withCount(['consumo as consultas_mes' => fn ($q) => $q->where('created_at', '>=', now()->startOfMonth())])
                ->get(),
            'planesApi' => ApiPlan::orderBy('precio_mensual')->orderBy('orden')->get(),
            'llaves' => \App\Models\ConsultaLlave::with(['empresa:id,razon_social,ruc', 'plan'])
                ->withCount(['consumo as usadas_mes' => fn ($q) => $q->where('created_at', '>=', now()->startOfMonth())])
                ->latest('id')
                ->get(),
            'empresas' => \App\Models\Company::orderBy('razon_social')->get(['id', 'razon_social', 'ruc']),
        ]);
    }

    /**
     * Crear un plan.
     *
     * Los paquetes que se venden son datos, no codigo: "Solo RUC", "Solo DNI"
     * y "Completo" son tres planes con distintas cuotas, y armarlos aqui evita
     * tener que inventar un mecanismo para cada combinacion que se ocurra.
     */
    public function guardarPlan(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:60',
            'descripcion' => 'nullable|string|max:120',
            'precio_mensual' => 'required|numeric|min:0|max:99999',
            'a_medida' => 'nullable|boolean',
        ]);

        $cuotas = (array) $request->input('cuotas', []);

        $plan = ApiPlan::create($datos + [
            'slug' => $this->slugLibre($datos['nombre']),
            'a_medida' => (bool) ($datos['a_medida'] ?? false),
            'activo' => true,
            'orden' => (int) ApiPlan::max('orden') + 1,
        ]);

        foreach (Api::pluck('id') as $api) {
            $plan->apis()->attach($api, ['limite_mensual' => max(0, (int) ($cuotas[$api] ?? 0))]);
        }

        return back()->with('success', "Plan «{$plan->nombre}» creado.");
    }

    public function actualizarPlan(Request $request, ApiPlan $plan)
    {
        $plan->update($request->validate([
            'nombre' => 'required|string|max:60',
            'descripcion' => 'nullable|string|max:120',
            'precio_mensual' => 'required|numeric|min:0|max:99999',
            'a_medida' => 'nullable|boolean',
        ]) + ['a_medida' => $request->boolean('a_medida')]);

        // syncWithoutDetaching y no sync: una consulta que no venga en el
        // formulario —porque se creo despues de abrir la pantalla— no debe
        // perder la cuota que ya tenia.
        foreach ((array) $request->input('cuotas', []) as $api => $tope) {
            $plan->apis()->syncWithoutDetaching([$api => ['limite_mensual' => max(0, (int) $tope)]]);
        }

        return back()->with('success', "Plan «{$plan->nombre}» actualizado.");
    }

    public function borrarPlan(ApiPlan $plan)
    {
        // Con llaves dentro no se borra: dejarlas sin plan seria cortarles el
        // servicio sin avisar. Primero se mueven a otro.
        $cuantas = \App\Models\ConsultaLlave::where('api_plan_id', $plan->id)->count();

        if ($cuantas > 0) {
            return back()->with('error',
                "No se puede borrar «{$plan->nombre}»: hay {$cuantas} llave(s) en él. "
                . 'Cámbialas de plan primero.');
        }

        $nombre = $plan->nombre;
        $plan->delete();

        return back()->with('success', "Plan «{$nombre}» eliminado.");
    }

    /** Un slug que no choque con otro plan. */
    private function slugLibre(string $nombre): string
    {
        $base = \Illuminate\Support\Str::slug($nombre) ?: 'plan';
        $slug = $base;
        $n = 2;

        while (ApiPlan::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    /**
     * Crear una api.
     *
     * El slug es lo que va en la direccion (/api/consultas/<slug>/…), asi que
     * no se puede cambiar despues sin romperle la integracion a quien ya la
     * use: se pide al crear y luego se queda quieto.
     */
    public function guardarApi(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:80',
            'slug' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9\-]+$/', 'unique:apis,slug'],
            'descripcion' => 'nullable|string|max:180',
        ], [
            'slug.regex' => 'El identificador va en minúsculas, sin espacios ni acentos. Ej: ruc, tipo-cambio.',
            'slug.unique' => 'Ya hay una consulta con ese identificador.',
        ]);

        $api = Api::create($datos + ['activa' => true, 'modo_prueba' => false]);

        // Nace en todos los planes con 0: existe pero no la incluye ninguno,
        // y se decide plan por plan en la pestaña de al lado. Mas prudente que
        // regalarla en todos sin querer.
        foreach (ApiPlan::pluck('id') as $plan) {
            $api->planes()->attach($plan, ['limite_mensual' => 0]);
        }

        return back()->with('success', "«{$api->nombre}» creada. Ponle cuotas en Planes para que alguien pueda usarla.");
    }

    public function actualizarApi(Request $request, Api $api)
    {
        $api->update($request->validate([
            'nombre' => 'required|string|max:80',
            'descripcion' => 'nullable|string|max:180',
        ]));

        return back()->with('success', 'Consulta actualizada.');
    }

    /** Encender/apagar, o entrar y salir del modo pruebas. */
    public function alternarApi(Request $request, Api $api)
    {
        $campo = $request->validate([
            'campo' => 'required|in:activa,modo_prueba',
        ])['campo'];

        $api->update([$campo => ! $api->$campo]);

        $aviso = $campo === 'activa'
            ? ($api->activa ? 'encendida' : 'apagada: responde 503 y no gasta cuota')
            : ($api->modo_prueba ? 'en modo pruebas: devuelve datos de ejemplo' : 'fuera del modo pruebas');

        return back()->with('success', "«{$api->nombre}» {$aviso}.");
    }

    public function borrarApi(Api $api)
    {
        // El consumo no se borra con ella: es lo que se cobro, y quedarse sin
        // el historial por retirar un servicio seria perder la contabilidad.
        // La columna api_id queda en nulo (nullOnDelete).
        $nombre = $api->nombre;
        $api->delete();

        return back()->with('success', "«{$nombre}» eliminada. El consumo que hubo se conserva.");
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

        // Contra el plan de CONSULTAS de la llave, no contra el de facturacion:
        // son dos cosas distintas y cruzarlas daria avisos falsos.
        $cercaDelTope = DB::table('consultas_consumo')
            ->join('consulta_llaves', 'consulta_llaves.id', '=', 'consultas_consumo.llave_id')
            ->join('api_plan_limite', function ($j) {
                $j->on('api_plan_limite.api_plan_id', '=', 'consulta_llaves.api_plan_id')
                  ->on('api_plan_limite.api_id', '=', 'consultas_consumo.api_id');
            })
            ->where('consultas_consumo.created_at', '>=', $mes)
            ->where('api_plan_limite.limite_mensual', '>', 0)
            // El select explicito no es adorno: con GROUP BY, MySQL rechaza el
            // "select *" que pone Laravel por defecto (only_full_group_by).
            ->select('consulta_llaves.id')
            ->groupBy('consulta_llaves.id', 'consultas_consumo.api_id', 'api_plan_limite.limite_mensual')
            ->havingRaw('COUNT(*) >= api_plan_limite.limite_mensual * 0.8')
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

    /** Quien consulta y cuanto gasta, por llave, ordenado por quien mas usa. */
    private function porLlave()
    {
        return DB::table('consultas_consumo')
            ->join('consulta_llaves', 'consulta_llaves.id', '=', 'consultas_consumo.llave_id')
            ->leftJoin('companies', 'companies.id', '=', 'consulta_llaves.company_id')
            ->leftJoin('api_planes', 'api_planes.id', '=', 'consulta_llaves.api_plan_id')
            ->where('consultas_consumo.created_at', '>=', now()->startOfMonth())
            ->groupBy('consulta_llaves.id', 'consulta_llaves.nombre', 'consulta_llaves.entorno',
                      'companies.razon_social', 'consulta_llaves.titular', 'api_planes.nombre')
            ->selectRaw('consulta_llaves.id, consulta_llaves.nombre as llave,
                         consulta_llaves.entorno,
                         COALESCE(companies.razon_social, consulta_llaves.titular) as titular,
                         api_planes.nombre as plan, COUNT(*) as usadas,
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
