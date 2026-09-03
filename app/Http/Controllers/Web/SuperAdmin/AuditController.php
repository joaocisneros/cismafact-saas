<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()
            ->with([
                'user:id,name,email',
                'company:id,razon_social,ruc',
            ]);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($search = $request->string('search')->trim()->toString()) {
            // Tambien por lo que se lee en pantalla: la frase y el nombre de
            // la pantalla se arman al pintarlos, asi que se traducen al reves
            // y se buscan las rutas que los dicen. Sin esto, buscar «secret» o
            // «Configuración» no daba ningun resultado.
            $rutas = \App\Support\AccionAuditada::rutasQueDicen($search);
            $piezas = collect($rutas)->filter(fn ($r) => str_starts_with($r, '__'))
                ->map(fn ($r) => substr($r, 2));
            $nombres = collect($rutas)->reject(fn ($r) => str_starts_with($r, '__'));

            $query->where(function ($auditQuery) use ($search, $nombres, $piezas) {
                $auditQuery->where('route_name', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->when($nombres->isNotEmpty(), fn ($q) => $q->orWhereIn('route_name', $nombres))
                    ->when($piezas->isNotEmpty(), fn ($q) => $piezas->each(
                        fn ($p) => $q->orWhere('route_name', 'like', "%{$p}%")
                    ));
            });
        }

        $logs = $query->latest('created_at')->simplePaginate(20)->withQueryString();

        $companies = Cache::remember('audit_company_options', now()->addMinutes(10), fn () => Company::orderBy('razon_social')
            ->limit(300)
            ->get(['id', 'razon_social']));

        $users = Cache::remember('audit_user_options', now()->addMinutes(10), fn () => User::orderBy('name')
            ->limit(300)
            ->get(['id', 'name', 'email']));

        return view('super-admin.audit.index', compact('logs', 'companies', 'users'));
    }
}
