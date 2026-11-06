<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Support\AuditoriaUnificada;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AuditController extends Controller
{
    private const POR_PAGINA = 20;

    public function index(Request $request): View
    {
        $filtros = [
            'user_id' => $request->filled('user_id') ? $request->integer('user_id') : null,
            'company_id' => $request->filled('company_id') ? $request->integer('company_id') : null,
            'action' => $request->input('action'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->string('search')->trim()->toString() ?: null,
            'fuente' => array_key_exists((string) $request->input('fuente'), AuditoriaUnificada::FUENTES)
                ? $request->input('fuente') : null,
            'entorno' => array_key_exists((string) $request->input('entorno'), AuditoriaUnificada::ENTORNOS)
                ? $request->input('entorno') : null,
        ];

        // Cada fuente solo necesita traer hasta el final de la página pedida,
        // más una fila para saber si hay siguiente.
        $pagina = max(1, Paginator::resolveCurrentPage());
        $tope = $pagina * self::POR_PAGINA + 1;

        $logs = AuditoriaUnificada::consulta($filtros, $tope)
            ->with([
                'user:id,name,email',
                'actor:id,name,email',
                'company:id,razon_social,ruc,modo_produccion',
            ])
            ->orderByDesc('created_at')
            ->simplePaginate(self::POR_PAGINA)
            ->withQueryString();

        $companies = Cache::remember('audit_company_options', now()->addMinutes(10), fn () => Company::orderBy('razon_social')
            ->limit(300)
            ->get(['id', 'razon_social']));

        $users = Cache::remember('audit_user_options', now()->addMinutes(10), fn () => User::orderBy('name')
            ->limit(300)
            ->get(['id', 'name', 'email']));

        $fuentes = AuditoriaUnificada::FUENTES;
        $entornos = AuditoriaUnificada::ENTORNOS;

        return view('super-admin.audit.index', compact('logs', 'companies', 'users', 'fuentes', 'entornos'));
    }
}
