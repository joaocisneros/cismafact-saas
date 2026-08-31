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
            'guardadas' => $this->guardadas(),
            // Las ultimas consultas, una por fila. Es lo que se mira cuando un
            // cliente dice "me falla": ahi se ve que pidio y que le pasó.
            //
            // Lo gastado por cada llave NO va aqui: eso ya sale en «Mis APIs»,
            // junto a su llave y con su tope al lado, que es donde se mira para
            // saber si a alguien le queda cuota. Tenerlo en dos sitios era
            // repetir lo mismo peor contado.
            'solo_fallos' => $fallos = request()->boolean('fallos'),
            // Cuantos han fallado este mes. Va en el boton del filtro: si pone
            // cero, no hace falta ni entrar a mirar.
            'fallos_mes' => DB::table('consultas_consumo')
                ->where('origen', 'externo')
                ->where('exito', false)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            // Resumen del mes: lo que se cobra, y de eso cuanto costo de verdad.
            'resumen_externo' => $this->resumen('externo'),
            'historial' => DB::table('consultas_consumo')
                ->where('consultas_consumo.origen', 'externo')
                ->when($fallos, fn ($q) => $q->where('consultas_consumo.exito', false))
                ->leftJoin('consulta_llaves', 'consulta_llaves.id', '=', 'consultas_consumo.llave_id')
                // El plan de la llave, para poner en cada fila lo que paga quien
                // consulto en vez de un «con costo» que no dice cuanto.
                ->leftJoin('api_planes', 'api_planes.id', '=', 'consulta_llaves.api_plan_id')
                ->leftJoin('companies', 'companies.id', '=', 'consultas_consumo.company_id')
                ->leftJoin('apis', 'apis.id', '=', 'consultas_consumo.api_id')
                ->leftJoin('consultas_documento', function ($j) {
                    $j->on('consultas_documento.tipo', '=', 'consultas_consumo.tipo')
                      ->on('consultas_documento.numero', '=', 'consultas_consumo.numero');
                })
                ->orderByDesc('consultas_consumo.id')
                ->limit(60)
                ->get([
                    'consultas_consumo.created_at',
                    'consultas_consumo.tipo',
                    'consultas_consumo.numero',
                    'consultas_consumo.fuente',
                    'consultas_consumo.exito',
                    'consultas_consumo.ms',
                    'consultas_consumo.motivo',
                    'consulta_llaves.nombre as llave',
                    'consulta_llaves.entorno',
                    'apis.nombre as servicio',
                    'consultas_documento.datos as ficha',
                    // Cuando la llave se borra, la empresa sigue ahi: sin esto
                    // la fila no identificaba a nadie.
                    'companies.razon_social as empresa',
                    'api_planes.nombre as plan',
                    'api_planes.precio_mensual as plan_precio',
                    'api_planes.a_medida as plan_a_medida',
                ]),
            'padron' => DB::table('padron_ruc')->count(),
            'apis' => Api::with(['planes' => fn ($q) => $q->orderBy('a_medida')->orderBy('precio_mensual')->orderBy('orden')])
                ->withCount(['consumo as consultas_mes' => fn ($q) => $q->where('created_at', '>=', now()->startOfMonth())])
                ->get(),
            'planesApi' => ApiPlan::orderBy('a_medida')->orderBy('precio_mensual')->orderBy('orden')->get(),
            // Separadas por entorno: las de prueba tienen su pestaña. Mezclarlas
            // con las que cobran confunde al mirar quien gasta que.
            'llaves' => \App\Models\ConsultaLlave::with(['empresa:id,razon_social,ruc', 'plan'])
                ->withCount(['consumo as usadas_mes' => fn ($q) => $q->where('created_at', '>=', now()->startOfMonth())])
                ->where('entorno', 'produccion')
                ->latest('id')
                ->get(),
            'sandbox' => \App\Models\ConsultaLlave::with(['empresa:id,razon_social,ruc', 'plan'])
                ->withCount('consumo')
                ->where('entorno', 'sandbox')
                ->latest('id')
                ->get(),
            'empresas' => \App\Models\Company::orderBy('razon_social')->get(['id', 'razon_social', 'ruc']),

            // Consumo interno: lo que gastan las empresas de casa buscando un
            // RUC o un DNI desde el panel. No se cobra, pero lo que sale al
            // proveedor se paga igual.
            'resumen_interno' => $this->resumen('interno'),
            'por_empresa' => $this->consumoPorEmpresa(),
            // Igual que en el externo: si pone cero, no hace falta ni entrar.
            'fallos_internos_mes' => DB::table('consultas_consumo')
                ->where('origen', 'interno')
                ->where('exito', false)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'historial_interno' => DB::table('consultas_consumo')
                ->where('consultas_consumo.origen', 'interno')
                ->when($fallos, fn ($q) => $q->where('consultas_consumo.exito', false))
                ->leftJoin('companies', 'companies.id', '=', 'consultas_consumo.company_id')
                // La ficha vive en la tabla de documentos consultados: aqui solo
                // se guarda que se pidio, no lo que se trajo.
                ->leftJoin('consultas_documento', function ($j) {
                    $j->on('consultas_documento.tipo', '=', 'consultas_consumo.tipo')
                      ->on('consultas_documento.numero', '=', 'consultas_consumo.numero');
                })
                ->orderByDesc('consultas_consumo.id')
                ->limit(40)
                ->get([
                    'consultas_consumo.created_at',
                    'consultas_consumo.company_id',
                    'consultas_consumo.tipo',
                    'consultas_consumo.numero',
                    'consultas_consumo.fuente',
                    'consultas_consumo.exito',
                    'consultas_consumo.ms',
                    'consultas_consumo.motivo',
                    'companies.razon_social as empresa',
                    // Una empresa dada de baja que sigue buscando es justo lo
                    // que se quiere ver de un vistazo.
                    'companies.activo as empresa_activa',
                    'companies.suspendida_manualmente',
                    // A quien corresponde el numero. Sin esto la fila decia que
                    // se busco un RUC y salio bien, pero no de quien era: habia
                    // que copiar el numero y buscarlo aparte.
                    'consultas_documento.datos as ficha',
                ]),

            // Lo que lleva cada empresa este mes, para ponerlo en su fila sin
            // volver a tener una tabla aparte solo para eso.
            'consumo_por_empresa' => DB::table('consultas_consumo')
                ->where('origen', 'interno')
                ->where('created_at', '>=', now()->startOfMonth())
                ->groupBy('company_id')
                ->pluck(DB::raw('COUNT(*)'), 'company_id'),
        ]);
    }

    /**
     * Las cuatro cifras del mes para un origen.
     *
     * «proveedor» se separa del resto porque es la unica fuente que cuesta
     * dinero: lo demas se resolvio en casa, con el padron o con algo ya
     * consultado antes.
     */
    private function resumen(string $origen): array
    {
        $base = fn () => DB::table('consultas_consumo')
            ->where('origen', $origen)
            ->where('created_at', '>=', now()->startOfMonth());

        return [
            'total' => $base()->count(),
            'proveedor' => $base()->where('fuente', 'proveedor')->count(),
            'en_casa' => $base()->whereIn('fuente', ['padron', 'consultado antes'])->count(),
            // Las de sandbox no salen a ningun lado ni tocan datos reales, pero
            // se sirvieron igual: sin contarlas, las cifras de la linea no
            // sumaban el total que tenian al lado.
            'de_prueba' => $base()->where('fuente', 'modo prueba')->count(),
            'fallidas' => $base()->where('exito', false)->count(),
            'ms_medio' => (int) round((float) $base()->where('exito', true)->avg('ms')),
        ];
    }

    /**
     * Quien usa el servicio, de mas a menos.
     *
     * El reparto entre lo que costo y lo que salio de casa NO va por empresa:
     * esa cuenta ya esta en la linea de resumen, del mes entero, y aqui solo
     * añadia dos columnas que casi siempre iban a cero.
     */
    private function consumoPorEmpresa()
    {
        return DB::table('consultas_consumo')
            ->where('consultas_consumo.origen', 'interno')
            ->where('consultas_consumo.created_at', '>=', now()->startOfMonth())
            ->leftJoin('companies', 'companies.id', '=', 'consultas_consumo.company_id')
            ->groupBy('consultas_consumo.company_id', 'companies.razon_social', 'companies.ruc')
            ->orderByDesc('total')
            ->get([
                'companies.razon_social as empresa',
                'companies.ruc',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN exito = 1 THEN 1 ELSE 0 END) as exitosas'),
                DB::raw('SUM(CASE WHEN exito = 0 THEN 1 ELSE 0 END) as fallidas'),
                DB::raw('MAX(consultas_consumo.created_at) as ultima'),
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
            // Lo de prueba no puede disparar el aviso: no tiene tope que agotar
            // y ensuciaria el unico numero que obliga a hacer algo.
            ->where('consulta_llaves.entorno', 'produccion')
            ->where('api_plan_limite.limite_mensual', '>', 0)
            // El select explicito no es adorno: con GROUP BY, MySQL rechaza el
            // "select *" que pone Laravel por defecto (only_full_group_by).
            ->select('consulta_llaves.id')
            ->groupBy('consulta_llaves.id', 'consultas_consumo.api_id', 'api_plan_limite.limite_mensual')
            ->havingRaw('COUNT(*) >= api_plan_limite.limite_mensual * 0.8')
            ->get()
            ->count();

        // Cuentan TODAS, tambien las fallidas. Antes solo contaban las que
        // salieron bien, pero el desglose de debajo («15 RUC · 9 DNI») venia de
        // otra cuenta que las incluia: la tarjeta ponia 18 encima de dos
        // numeros que sumaban 24.
        return [
            'mes' => DB::table('consultas_consumo')->where('created_at', '>=', $mes)->count(),
            'hoy' => DB::table('consultas_consumo')->where('created_at', '>=', now()->startOfDay())->count(),
            // Partido por origen: es lo que desglosan las dos pestañas de
            // consumo, y asi se ve de donde sale cada cifra sin sumar a mano.
            'mes_externo' => DB::table('consultas_consumo')->where('origen', 'externo')->where('created_at', '>=', $mes)->count(),
            'mes_interno' => DB::table('consultas_consumo')->where('origen', 'interno')->where('created_at', '>=', $mes)->count(),
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
