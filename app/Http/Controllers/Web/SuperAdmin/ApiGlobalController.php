<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ApiUsage;
use App\Models\ApiKey;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ApiGlobalController extends Controller
{
    public function index()
    {
        $startOfToday = now()->copy()->startOfDay();
        $endOfToday = now()->copy()->endOfDay();
        $startOfMonth = now()->copy()->startOfMonth();
        $endOfMonth = now()->copy()->endOfMonth();

        $data = Cache::remember('api_global_index', 60, function () use ($startOfToday, $endOfToday, $startOfMonth, $endOfMonth) {
            /*
             * Sin el sandbox: esas cifras son de este modulo, y este modulo
             * lista empresas reales.
             *
             * Contandolo, la cabecera decia «5 errores hoy» y ninguna fila de
             * abajo tenia ninguno, porque eran todos de la empresa demo. Un
             * numero que no se puede rastrear no sirve para nada, y lo del
             * sandbox se mira en Sandbox Facturacion.
             */
            $stats = ApiUsage::whereHas('company', fn ($q) => $q->where('es_demo', false))
                ->selectRaw("
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as hoy,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as mes,
                SUM(CASE WHEN created_at BETWEEN ? AND ? AND status_code >= 400 THEN 1 ELSE 0 END) as errores_hoy,
                AVG(CASE WHEN created_at BETWEEN ? AND ? THEN response_time_ms ELSE NULL END) as tiempo_promedio_hoy
            ")
            ->addBinding([
                $startOfToday, $endOfToday,
                $startOfMonth, $endOfMonth,
                $startOfToday, $endOfToday,
                $startOfToday, $endOfToday,
            ], 'select')
            ->first();

            return [
                'consumoHoy' => (int)($stats->hoy ?? 0),
                'consumoMes' => (int)($stats->mes ?? 0),
                'erroresHoy' => (int)($stats->errores_hoy ?? 0),
                'apiKeyActivas' => ApiKey::where('active', true)
                    ->whereHas('company', fn ($q) => $q->where('es_demo', false))->count(),
                'tiempoPromedio' => round((float)($stats->tiempo_promedio_hoy ?? 0), 2),
            ];
        });

        // El eje de esta pantalla es la EMPRESA: cuanto consume de su cupo
        // mensual y si puedes cortarle el acceso. Antes listaba credenciales
        // sueltas, que no dicen quien esta cerca de pasarse.
        $inicioMes = now()->startOfMonth();

        // Las llamadas del mes y cuantas fallaron, de una vez: son la misma
            // tabla y el mismo recorrido.
        $consumoPorEmpresa = ApiUsage::selectRaw(
                'company_id, COUNT(*) as total,
                 SUM(status_code >= 400) as errores,
                 SUM(created_at >= ?) as hoy', [now()->startOfDay()])
            ->whereHas('apiKey', fn ($q) => $q->where('origen', 'empresa'))
            ->where('created_at', '>=', $inicioMes)
            ->groupBy('company_id')
            ->get()
            ->keyBy('company_id');

        $llamadasPorEmpresa = $consumoPorEmpresa->map(fn ($f) => (int) $f->total);

        $deLaEmpresa = fn ($q) => $q->where('origen', 'empresa');

        $empresas = Company::with([
                'plan:id,name,api_request_limit',
                'apiKeys' => fn ($q) => $q->where('origen', 'empresa')
                    ->select('id', 'company_id', 'name', 'last_used_at'),
            ])
            ->withCount([
                'apiKeys as api_keys_count' => $deLaEmpresa,
                'apiKeys as api_keys_activas_count' => fn ($q) => $q->where('origen', 'empresa')->where('active', true),
            ])
            // Los clientes, siempre. La empresa demo, solo cuando alguien le
            // ha creado una credencial entrando a su panel: entonces es una
            // credencial de empresa y hay que poder verla desde aqui.
            ->where(fn ($q) => $q->where('es_demo', false)
                ->orWhereHas('apiKeys', fn ($k) => $k->where('origen', 'empresa')))
            ->orderBy('razon_social')
            ->get()
            ->map(function ($company) use ($llamadasPorEmpresa, $consumoPorEmpresa) {
                $limite = (int) ($company->plan->api_request_limit ?? 0);
                $usado = (int) ($llamadasPorEmpresa[$company->id] ?? 0);

                return [
                    'modelo' => $company,
                    'plan' => $company->plan->name ?? 'Sin plan',
                    'limite' => $limite,
                    'usado' => $usado,
                    'ilimitado' => $limite <= 0,
                    'porcentaje' => $limite > 0 ? min(100, (int) round($usado * 100 / $limite)) : 0,
                    'credenciales' => (int) $company->api_keys_count,
                    // Como la llamo su dueño: «ERP», «tienda web»… Es lo unico
                    // que dice para que la usa, y habia que abrir la ficha.
                    'nombres' => $company->apiKeys->pluck('name'),
                    // Las que fallaron este mes: un cliente con errores necesita
                    // una llamada, y ese es el dato por el que se mira aqui.
                    'errores' => (int) ($consumoPorEmpresa[$company->id]->errores ?? 0),
                    // Las de hoy. El consumo del mes ya sale al lado, asi que
                    // repetirlo no añadia nada: lo que no se veia es si hoy hay
                    // movimiento.
                    'hoy' => (int) ($consumoPorEmpresa[$company->id]->hoy ?? 0),
                    // Cuando llamo por ultima vez. Una credencial habilitada que
                    // no se usa nunca es una integracion que no arranco, y eso no
                    // se veia en ningun sitio.
                    'ultimo_uso' => $company->apiKeys->max('last_used_at'),
                    // Cual es, cuando solo hay una: asi se le puede generar el
                    // secret desde la propia fila. Con varias no se sabria a
                    // cual se refiere el boton, y hay que entrar a elegir.
                    'unica' => $company->api_keys_count === 1 ? $company->apiKeys->first() : null,
                    'activas' => (int) $company->api_keys_activas_count,
                ];
            });

        return view('super-admin.api-global', $data + [
            'empresas' => $empresas,
        ]);
    }

    public function logs(Request $request)
    {
        $query = ApiUsage::with('company:id,razon_social');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('status')) {
            $query->where('status_code', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->ajax() || $request->boolean('modal')) {
            $logs = $query->latest()->limit(20)->get();
            return view('super-admin.api-global._logs_modal', compact('logs'));
        }

        $logs = $query->latest()->paginate(20)->withQueryString();
        $companies = Company::orderBy('razon_social')->limit(300)->get(['id', 'razon_social']);

        return view('super-admin.api-global.logs', compact('logs', 'companies'));
    }

    /**
     * Actividad de una credencial: cuanto se usa y, sobre todo, que le falla.
     *
     * Va aparte de las credenciales a proposito. Son dos preguntas distintas:
     * "que le paso a este dev" no se responde en la misma ventana donde se
     * copian la key y el secret.
     */
    public function apiKeyActividad(ApiKey $apiKey)
    {
        $apiKey->load('company');

        $totalUsage = ApiUsage::where('api_key_id', $apiKey->id)->count();
        $totalErrores = ApiUsage::where('api_key_id', $apiKey->id)->where('status_code', '>=', 400)->count();

        // Con miles de llamadas no se pueden traer todas: se ensena una pagina
        // y se deja filtrar por las que fallaron, que es lo que se busca.
        $soloErrores = request()->get('solo') === 'errores';

        $usos = ApiUsage::where('api_key_id', $apiKey->id)
            ->when($soloErrores, fn ($q) => $q->where('status_code', '>=', 400))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('super-admin.api-global._key_actividad', compact(
            'apiKey', 'usos', 'totalUsage', 'totalErrores', 'soloErrores'
        ));
    }

    public function showApiKey(ApiKey $apiKey)
    {
        $apiKey->load('company');
        $recentUsage = ApiUsage::where('api_key_id', $apiKey->id)
            ->latest()
            ->take(10)
            ->get();

        $totalUsage = ApiUsage::where('api_key_id', $apiKey->id)->count();

        if (request()->ajax() || request()->boolean('modal')) {
            return view('super-admin.api-global._key_modal', compact('apiKey', 'recentUsage', 'totalUsage'));
        }

        return view('super-admin.api-global.show-key', compact('apiKey', 'recentUsage', 'totalUsage'));
    }

    /**
     * Corta o devuelve el acceso por API a una empresa entera.
     *
     * Actua sobre todas sus credenciales a la vez: ir bloqueandolas una por una
     * dejaba huecos, porque basta con que quede una activa para seguir emitiendo.
     */
    public function toggleCompanyApi(Company $company)
    {
        $tieneActivas = $company->apiKeys()->where('active', true)->exists();

        $company->apiKeys()->update(['active' => ! $tieneActivas]);

        Cache::forget('api_global_index');

        return back()->with('success', $tieneActivas
            ? "Acceso por API cortado para {$company->razon_social}. Sus integraciones dejarán de emitir."
            : "Acceso por API restablecido para {$company->razon_social}.");
    }

    public function toggleApiKey(ApiKey $apiKey)
    {
        $apiKey->update(['active' => !$apiKey->active]);
        Cache::forget('api_global_index');
        Cache::forget('api_global_performance');

        $status = $apiKey->active ? 'activada' : 'desactivada';
        return back()->with('success', "API Key {$status} exitosamente.");
    }

    /**
     * Genera un token SANDBOX (de prueba) para entregar a un programador.
     * La llave se crea sobre la empresa marcada como demo (es_demo) y emite
     * solo a SUNAT beta, sin tope. Cada token lleva el nombre del dev para
     * poder identificar su consumo y bloquearlo individualmente.
     */
    public function generateSandboxToken(Request $request)
    {
        $validated = $request->validate([
            'dev_name' => ['required', 'string', 'max:120'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $demo = Company::where('es_demo', true)->first();

        if (! $demo) {
            return back()->with('error', 'No hay ninguna empresa marcada como DEMO/SANDBOX. Marca una empresa con "es_demo" primero (en el detalle de la empresa).');
        }

        $plainSecret = ApiKey::generateSecret();

        $apiKey = ApiKey::create([
            'company_id' => $demo->id,
            'name' => 'Sandbox - ' . $validated['dev_name'],
            'key' => ApiKey::generateKey(),
            'secret' => $plainSecret,
            'abilities' => ['*'],
            'active' => true,
            'expires_at' => isset($validated['expires_in_days'])
                ? now()->addDays((int) $validated['expires_in_days'])
                : null,
        ]);

        Cache::forget('api_global_index');

        /*
         * El secreto se entrega aqui, y solo aqui.
         *
         * Decia que se consultaba con el boton «Ver» de la fila, y esa ficha
         * nunca ha enseñado el secret: solo la key. El token se generaba y no
         * habia forma de saber con que se usa, salvo entrar como la empresa
         * demo y mirarlo desde su pantalla.
         *
         * Y ya no queda copia que consultar: se guarda como hash, asi que esta
         * es la unica vez que se puede leer. Si se pierde, se regenera.
         */
        return back()->with('success', "Token de «{$apiKey->name}» generado.");
    }

    /**
     * El secret de una credencial, solo cuando se pide.
     *
     * No viaja con el listado: ahi saldria el de todas las credenciales en
     * cada carga de la pagina. Se trae el de la que se abre.
     */
    public function secretoApiKey(ApiKey $apiKey)
    {
        return response()->json(['secret' => $apiKey->secret]);
    }

    /**
     * Un secret nuevo para una credencial que ya existe.
     *
     * La key no se toca: el cliente solo tiene que cambiar el secret en su
     * sistema, y lo demas —su integracion, sus permisos, su historial— sigue
     * igual. Sin esto, un cliente que perdia el secret obligaba a borrarle la
     * credencial y darle otra, con key nueva, y a rehacer su configuracion
     * entera por algo que no era culpa suya.
     *
     * Corta al instante: el secret anterior deja de valer en cuanto se guarda,
     * asi que hay que pasarle el nuevo. Por eso se enseña aqui una vez.
     */
    public function regenerateApiKey(ApiKey $apiKey)
    {
        // Aqui solo las de empresa. Los tokens de sandbox no salen en la lista,
        // pero la ruta seguia aceptando su id: se le podia cortar la conexion
        // a un programador desde el modulo equivocado y sin verlo.
        if ($apiKey->origen !== 'empresa') {
            return back()->with('error',
                'Ese es un token de sandbox. Su secret se cambia desde Sandbox Facturación, '
                . 'que es donde se ve a qué programador se le entregó.');
        }

        $plainSecret = ApiKey::generateSecret();

        $apiKey->update(['secret' => $plainSecret]);

        Cache::forget('api_global_index');

        return back()->with('success', "Secret de «{$apiKey->name}» regenerado. El anterior ya no funciona.");
    }

    /**
     * Amplía la vigencia de un token: suma N días a su caducidad (o desde hoy
     * si ya venció / no tenía). Sirve para extender un token sandbox sin crear
     * otro.
     */
    public function extendApiKey(Request $request, ApiKey $apiKey)
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $base = $apiKey->expires_at && $apiKey->expires_at->isFuture()
            ? $apiKey->expires_at
            : now();

        $apiKey->update(['expires_at' => $base->copy()->addDays((int) $validated['days'])]);
        Cache::forget('api_global_index');

        return back()->with('success', "Token extendido {$validated['days']} días. Nueva caducidad: {$apiKey->expires_at->format('d/m/Y')}.");
    }

    /**
     * Elimina una API Key (se usa para los tokens sandbox que ya no se quieren).
     * A diferencia de "bloquear", esto la borra definitivamente.
     */
    public function destroyApiKey(ApiKey $apiKey)
    {
        if ($apiKey->origen !== 'sandbox') {
            return back()->with('error',
                'Esa credencial no es un token de sandbox: es de una empresa. Borrarla no se puede '
                . 'deshacer, pierde su key y hay que rehacerle la integración. Usa «Bloquear», que sí se deshace.');
        }

        $nombre = $apiKey->name;
        $apiKey->delete();
        Cache::forget('api_global_index');

        return back()->with('success', "Token \"{$nombre}\" eliminado.");
    }

    public function apiKeys(Request $request)
    {
        // Solo llaves de empresas reales. Los tokens de empresas demo/sandbox
        // se gestionan en su propia tabla (sección "Token Sandbox").
        $query = ApiKey::with('company:id,razon_social')
            ->where('origen', 'empresa');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('key', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($q2) use ($search) {
                      $q2->where('razon_social', 'like', "%{$search}%")
                         ->orWhere('ruc', 'like', "%{$search}%");
                  });
            });
        }
        if ($request->filled('status')) {
            $query->where('active', $request->status === 'active');
        }
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->ajax() || $request->boolean('modal')) {
            // El modal ya ensena las credenciales enteras al desplegarlas, asi
            // que necesita la empresa (para saber si es sandbox) y el consumo.
            $apiKeys = $query->with('company')->latest()->limit(20)->get();

            $consumo = ApiUsage::selectRaw('api_key_id, COUNT(*) as total')
                ->whereIn('api_key_id', $apiKeys->pluck('id'))
                ->groupBy('api_key_id')
                ->pluck('total', 'api_key_id');

            return view('super-admin.api-global._api_keys_modal', compact('apiKeys', 'consumo'));
        }

        $apiKeys = $query->latest()->paginate(15)->withQueryString();
        $companies = Company::where('es_demo', false)->orderBy('razon_social')->limit(300)->get(['id', 'razon_social']);

        return view('super-admin.api-global.api-keys', compact('apiKeys', 'companies'));
    }

    public function performance()
    {
        $data = Cache::remember('api_global_performance', 60, function () {
            $start = now()->subDays(6)->startOfDay();
            $end = now()->endOfDay();
            $start30 = now()->subDays(30)->startOfDay();

            $dailyData = ApiUsage::whereBetween('created_at', [$start, $end])
                ->selectRaw("DATE(created_at) as fecha, AVG(response_time_ms) as avg_time, MAX(response_time_ms) as max_time, MIN(response_time_ms) as min_time, COUNT(*) as total")
                ->groupBy('fecha')
                ->get()
                ->mapWithKeys(fn($r) => [$r->fecha => $r]);

            $days = collect();
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $d = $dailyData[$date] ?? null;
                $days->push([
                    'fecha' => now()->subDays($i)->format('d/m'),
                    'avg_time' => $d ? round($d->avg_time, 2) : 0,
                    'max_time' => $d ? round($d->max_time, 2) : 0,
                    'min_time' => $d ? round($d->min_time, 2) : 0,
                    'total' => $d ? (int)$d->total : 0,
                ]);
            }

            $topEndpoints = ApiUsage::select('path', DB::raw('count(*) as total'), DB::raw('avg(response_time_ms) as avg_time'), DB::raw('max(response_time_ms) as max_time'))
                ->where('created_at', '>=', $start30)
                ->groupBy('path')
                ->orderByDesc('total')
                ->take(10)
                ->get();

            $statusDistribution = ApiUsage::select('status_code', DB::raw('count(*) as total'))
                ->where('created_at', '>=', $start30)
                ->groupBy('status_code')
                ->orderByDesc('total')
                ->get();

            $erroresPorEmpresa = ApiUsage::with('company:id,razon_social')
                ->where('status_code', '>=', 400)
                ->where('created_at', '>=', $start30)
                ->select('company_id', DB::raw('count(*) as total'))
                ->groupBy('company_id')
                ->orderByDesc('total')
                ->take(10)
                ->get();

            $totalRequests = ApiUsage::where('created_at', '>=', $start30)->count();
            $exitosos = ApiUsage::where('created_at', '>=', $start30)->where('status_code', '<', 400)->count();
            $uptime = $totalRequests > 0 ? round(($exitosos / $totalRequests) * 100, 1) : 100;
            $avgResponseTime = ApiUsage::where('created_at', '>=', $start30)->avg('response_time_ms') ?? 0;

            return [
                'dailyPerformance' => $days,
                'topEndpoints' => $topEndpoints,
                'statusDistribution' => $statusDistribution,
                'erroresPorEmpresa' => $erroresPorEmpresa,
                'uptime' => $uptime,
                'avgResponseTime' => round($avgResponseTime, 2),
                'totalRequests' => $totalRequests,
            ];
        });

        if (request()->ajax() || request()->boolean('modal')) {
            return view('super-admin.api-global._performance_modal', $data);
        }

        return view('super-admin.api-global.performance', $data);
    }

}
