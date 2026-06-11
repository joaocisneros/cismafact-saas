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
            $query->where(function ($auditQuery) use ($search) {
                $auditQuery->where('route_name', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
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
