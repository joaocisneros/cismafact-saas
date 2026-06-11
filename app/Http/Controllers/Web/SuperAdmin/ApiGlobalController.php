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
            $stats = ApiUsage::selectRaw("
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
                'apiKeyActivas' => ApiKey::where('active', true)->count(),
                'tiempoPromedio' => round((float)($stats->tiempo_promedio_hoy ?? 0), 2),
            ];
        });

        // Tokens sandbox = API keys de empresas marcadas como demo. Se listan
        // bajo el formulario para verlos, extenderlos o bloquearlos ahí mismo.
        $sandboxTokens = ApiKey::whereHas('company', fn ($q) => $q->where('es_demo', true))
            ->with('company:id,razon_social')
            ->latest()
            ->get();

        return view('super-admin.api-global', $data + ['sandboxTokens' => $sandboxTokens]);
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
            'secret' => \Illuminate\Support\Facades\Hash::make($plainSecret),
            'plain_secret' => $plainSecret,
            'abilities' => ['*'],
            'active' => true,
            'expires_at' => isset($validated['expires_in_days'])
                ? now()->addDays((int) $validated['expires_in_days'])
                : null,
        ]);

        Cache::forget('api_global_index');

        // El secreto solo se muestra una vez, recién creado, para copiarlo.
        return back()->with('sandbox_token', [
            'name' => $apiKey->name,
            'key' => $apiKey->key,
            'secret' => $plainSecret,
        ])->with('success', 'Token sandbox generado. Cópialo ahora (el secreto no se vuelve a mostrar).');
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
        $nombre = $apiKey->name;
        $apiKey->delete();
        Cache::forget('api_global_index');

        return back()->with('success', "Token \"{$nombre}\" eliminado.");
    }

    public function apiKeys(Request $request)
    {
        $query = ApiKey::with('company:id,razon_social');

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
            $apiKeys = $query->latest()->limit(20)->get();
            return view('super-admin.api-global._api_keys_modal', compact('apiKeys'));
        }

        $apiKeys = $query->latest()->paginate(15)->withQueryString();
        $companies = Company::orderBy('razon_social')->limit(300)->get(['id', 'razon_social']);

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

        return view('super-admin.api-global._performance_modal', $data);
    }

}
