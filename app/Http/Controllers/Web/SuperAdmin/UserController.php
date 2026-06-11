<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\Role;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with([
            'company:id,razon_social',
            'role:id,name,display_name',
        ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('status')) {
            $query->where('active', $request->status === 'active');
        }

        $users = $query->latest()->simplePaginate(15)->withQueryString();
        $companies = Cache::remember('super_admin_company_filter_options', now()->addMinutes(5), fn () => Company::orderBy('razon_social')
            ->limit(300)
            ->get(['id', 'razon_social']));

        return view('super-admin.users.index', compact('users', 'companies'));
    }

    public function create()
    {
        $companies = Company::orderBy('razon_social')->get();
        $roles = Role::where('name', '!=', 'super_admin')->get();

        if (request()->ajax() || request()->boolean('modal')) {
            return view('super-admin.users._form_modal', compact('companies', 'roles'));
        }

        return view('super-admin.users.create', compact('companies', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'company_id' => 'required|exists:companies,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $company = Company::with('plan')->findOrFail($validated['company_id']);
        $limitService = app(PlanLimitService::class);

        if ($company->plan && $limitService->limitReached(
            $company->plan->user_limit,
            $limitService->usersUsed($company)
        )) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['company_id' => 'La empresa alcanzó el límite de usuarios de su plan.']);
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'company_id' => $validated['company_id'],
            'role_id' => $validated['role_id'],
            'active' => true,
        ]);

        return redirect()->route('super-admin.users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    public function show(User $user)
    {
        $user->load(['company', 'role']);
        $loginHistory = DB::table('login_history')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        if (request()->ajax() || request()->boolean('modal')) {
            return view('super-admin.users._detail_modal', compact('user', 'loginHistory'));
        }

        return view('super-admin.users.show', compact('user', 'loginHistory'));
    }

    public function edit(User $user)
    {
        $companies = Company::orderBy('razon_social')->get();
        $roles = Role::where('name', '!=', 'super_admin')->get();

        if (request()->ajax() || request()->boolean('modal')) {
            return view('super-admin.users._form_modal', compact('user', 'companies', 'roles'));
        }

        return view('super-admin.users.edit', compact('user', 'companies', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'company_id' => 'required|exists:companies,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ((int) $user->company_id !== (int) $validated['company_id']) {
            $company = Company::with('plan')->findOrFail($validated['company_id']);
            $limitService = app(PlanLimitService::class);

            if ($company->plan && $limitService->limitReached(
                $company->plan->user_limit,
                $limitService->usersUsed($company)
            )) {
                return back()
                    ->withInput()
                    ->withErrors(['company_id' => 'La empresa de destino alcanzó el límite de usuarios de su plan.']);
            }
        }

        $user->update($validated);

        return redirect()->route('super-admin.users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    public function toggleLock(User $user)
    {
        if ($user->hasRole('super_admin')) {
            return back()->with('error', 'No se puede bloquear a un Super Admin.');
        }

        $user->update([
            'locked_until' => $user->locked_until && $user->locked_until->isFuture()
                ? null
                : now()->addDays(30),
        ]);

        $status = $user->locked_until && $user->locked_until->isFuture() ? 'bloqueado' : 'desbloqueado';

        return back()->with('success', "Usuario {$status} exitosamente.");
    }

    public function toggleActive(User $user)
    {
        if ($user->hasRole('super_admin')) {
            return back()->with('error', 'No se puede desactivar a un Super Admin.');
        }

        $user->update(['active' => !$user->active]);

        $status = $user->active ? 'activado' : 'desactivado';

        return back()->with('success', "Usuario {$status} exitosamente.");
    }

    public function resetPassword(Request $request, User $user)
    {
        if ($user->hasRole('super_admin')) {
            return back()->with('error', 'No se puede restablecer la contraseña de un Super Admin desde este módulo.');
        }

        $request->validate([
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($request->new_password),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        return back()->with('success', 'Contraseña restablecida exitosamente.');
    }

    public function resetPasswordForm(User $user)
    {
        abort_if($user->hasRole('super_admin'), 403, 'No se puede modificar un Super Admin.');

        return view('super-admin.users._reset_password_modal', compact('user'));
    }

    public function activity(User $user)
    {
        $loginHistory = DB::table('login_history')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->simplePaginate(20);

        return view('super-admin.users._activity_modal', compact('user', 'loginHistory'));
    }

    public function destroy(User $user)
    {
        if ($user->hasRole('super_admin')) {
            return back()->with('error', 'No se puede eliminar un Super Admin.');
        }
        $user->delete();
        return redirect()->route('super-admin.users.index')->with('success', 'Usuario eliminado.');
    }

}
