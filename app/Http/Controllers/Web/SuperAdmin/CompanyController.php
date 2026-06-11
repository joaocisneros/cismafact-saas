<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ApiUsage;
use App\Models\Company;
use App\Models\Correlative;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::query()
            ->with('plan:id,name')
            ->withCount(['invoices', 'boletas', 'creditNotes', 'debitNotes', 'dispatchGuides', 'apiKeys']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('razon_social', 'like', "%{$search}%")
                    ->orWhere('ruc', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('ruc')) {
            $query->where('ruc', 'like', '%' . $request->input('ruc') . '%');
        }

        if ($request->filled('razon_social')) {
            $query->where('razon_social', 'like', '%' . $request->input('razon_social') . '%');
        }

        if ($request->filled('status')) {
            $query->where('activo', $request->status === 'active');
        }

        $companies = $query->latest()->simplePaginate(15)->withQueryString();

        return view('super-admin.companies.index', compact('companies'));
    }

    public function create()
    {
        if (request()->ajax() || request()->boolean('modal')) {
            return view('super-admin.companies._create_modal');
        }

        return view('super-admin.companies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ruc' => 'required|string|size:11|unique:companies,ruc',
            'razon_social' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'direccion' => 'nullable|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $freePlan = Plan::where('code', 'free')->firstOrFail();

        $company = DB::transaction(function () use ($validated, $freePlan) {
            $company = Company::create([
                'ruc' => $validated['ruc'],
                'plan_id' => $freePlan->id,
                'razon_social' => $validated['razon_social'],
                'nombre_comercial' => $validated['razon_social'],
                'email' => $validated['email'],
                'direccion' => $validated['direccion'] ?? 'Direccion pendiente',
                'ubigeo' => '150101',
                'distrito' => 'Lima',
                'provincia' => 'Lima',
                'departamento' => 'Lima',
                'usuario_sol' => 'PENDIENTE',
                'clave_sol' => 'PENDIENTE',
                'activo' => true,
                'modo_produccion' => false,
            ]);

            $branch = $company->branches()->create([
                'codigo' => '0000',
                'nombre' => 'Principal',
                'direccion' => $company->direccion,
                'ubigeo' => $company->ubigeo,
                'distrito' => $company->distrito,
                'provincia' => $company->provincia,
                'departamento' => $company->departamento,
                'email' => $company->email,
            ]);

            foreach ([
                ['tipo_documento' => '01', 'serie' => 'F001'],
                ['tipo_documento' => '03', 'serie' => 'B001'],
                ['tipo_documento' => '07', 'serie' => 'FC01'],
                ['tipo_documento' => '08', 'serie' => 'FD01'],
                ['tipo_documento' => '09', 'serie' => 'T001'],
            ] as $documentSeries) {
                Correlative::create([
                    'branch_id' => $branch->id,
                    ...$documentSeries,
                    'correlativo_actual' => 0,
                ]);
            }

            $role = Role::where('name', 'company_admin')->firstOrFail();

            User::create([
                'name' => $validated['razon_social'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $role->id,
                'company_id' => $company->id,
                'active' => true,
            ]);

            $plainSecret = ApiKey::generateSecret();

            ApiKey::create([
                'company_id' => $company->id,
                'name' => 'API Key Principal',
                'key' => ApiKey::generateKey(),
                'secret' => Hash::make($plainSecret),
                'plain_secret' => $plainSecret,
                'abilities' => ['*'],
                'active' => true,
            ]);

            Subscription::create([
                'company_id' => $company->id,
                'plan_id' => $freePlan->id,
                'status' => 'active',
                'starts_at' => now()->toDateString(),
                'monthly_price' => $freePlan->monthly_price,
                'auto_renew' => false,
            ]);

            return $company;
        });

        $this->forgetCompanyCaches();

        return redirect()->route('super-admin.companies.show', $company)
            ->with('success', 'Empresa creada exitosamente.');
    }

    public function show(Company $company)
    {
        $company->load('plan:id,name');
        $company->loadCount(['invoices', 'boletas', 'creditNotes', 'debitNotes', 'dispatchGuides', 'users', 'apiKeys']);

        $apiKeys = $company->apiKeys()
            ->latest()
            ->limit(10)
            ->get(['id', 'company_id', 'name', 'key', 'secret', 'plain_secret', 'active', 'last_used_at', 'created_at']);

        $users = $company->users()
            ->with('role:id,name,display_name')
            ->latest()
            ->limit(10)
            ->get(['id', 'company_id', 'role_id', 'name', 'email', 'active', 'created_at']);

        $apiUsage = [
            'today' => ApiUsage::where('company_id', $company->id)->whereDate('created_at', today())->count(),
            'month' => ApiUsage::where('company_id', $company->id)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'avg_response' => (int) ApiUsage::where('company_id', $company->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->avg('response_time_ms'),
        ];

        $recentDocs = $this->recentDocuments($company->id);

        if (request()->ajax() || request()->boolean('modal')) {
            return view('super-admin.companies._modal', compact('company', 'recentDocs', 'apiKeys', 'users', 'apiUsage'));
        }

        return view('super-admin.companies.show', compact('company', 'recentDocs', 'apiKeys', 'users', 'apiUsage'));
    }

    public function edit(Company $company)
    {
        if (request()->ajax() || request()->boolean('modal')) {
            return view('super-admin.companies._edit_modal', compact('company'));
        }

        return view('super-admin.companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'required|email|max:255|unique:companies,email,' . $company->id,
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            // logo_path ya incluye el prefijo "logos/" porque store('logos', ...)
            // lo devuelve completo. Se borra usando la ruta tal cual, sin volver
            // a anteponer "logos/" (eso generaba "logos/logos/..." y dejaba
            // archivos huerfanos sin borrar).
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        $company->update($validated);
        $this->forgetCompanyCaches();

        return redirect()->route('super-admin.companies.show', $company)
            ->with('success', 'Empresa actualizada exitosamente.');
    }

    public function destroy(Company $company)
    {
        $company->delete();
        $this->forgetCompanyCaches();

        return redirect()->route('super-admin.companies.index')
            ->with('success', 'Empresa eliminada exitosamente.');
    }

    public function toggleStatus(Company $company)
    {
        $company->update(['activo' => ! $company->activo]);
        $this->forgetCompanyCaches();

        $status = $company->activo ? 'reactivada' : 'suspendida';

        return back()->with('success', "Empresa {$status} exitosamente.");
    }

    public function toggleDemo(Company $company)
    {
        $company->update(['es_demo' => ! $company->es_demo]);
        $this->forgetCompanyCaches();

        $estado = $company->es_demo
            ? 'marcada como DEMO/SANDBOX (sin tope, no cuenta como cliente real)'
            : 'desmarcada como demo (vuelve a ser empresa normal)';

        return back()->with('success', "Empresa {$estado}.");
    }

    private function recentDocuments(int $companyId)
    {
        $queries = collect([
            $this->recentDocumentQuery('invoices', 'Factura', $companyId),
            $this->recentDocumentQuery('boletas', 'Boleta', $companyId),
            $this->recentDocumentQuery('credit_notes', 'Nota Credito', $companyId),
            $this->recentDocumentQuery('debit_notes', 'Nota Debito', $companyId),
            $this->recentDocumentQuery('dispatch_guides', 'Guia Remision', $companyId),
        ]);

        $union = $queries->reduce(fn ($query, $next) => $query ? $query->unionAll($next) : $next);

        return DB::query()
            ->fromSub($union, 'documents')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    private function recentDocumentQuery(string $table, string $type, int $companyId)
    {
        return DB::table($table)
            ->where("{$table}.company_id", $companyId)
            ->selectRaw('? as tipo', [$type])
            ->addSelect([
                "{$table}.numero_completo",
                "{$table}.estado_sunat",
                "{$table}.created_at",
            ]);
    }

    private function forgetCompanyCaches(): void
    {
        Cache::forget('super_admin_company_filter_options');
        Cache::forget('super_admin_company_plan_options');
        Cache::forget('super_admin_dashboard_minimal_stats');
        Cache::forget('super_admin_dashboard_alerts');
    }
}
