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
    /** Roles que no pertenecen a una empresa concreta. */
    private const ROLES_SIN_EMPRESA = ['contador', 'super_admin'];

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
        $roles = $this->rolesAsignables();

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
            'company_id' => [$this->exigeEmpresa($request) ? 'required' : 'nullable', 'exists:companies,id'],
            'role_id' => 'required|exists:roles,id',
        ], [], ['company_id' => 'empresa']);

        $this->rolAsignable($request);

        // Un contador es de la plataforma, no de una empresa: no consume cupo
        // de usuarios de ningun plan.
        $companyId = $this->exigeEmpresa($request) ? $validated['company_id'] : null;

        if ($companyId) {
            $company = Company::with('plan')->findOrFail($companyId);
            $limitService = app(PlanLimitService::class);

            if ($company->plan && $limitService->limitReached(
                $company->plan->user_limit,
                $limitService->usersUsed($company)
            )) {
                return back()
                    ->withInput($request->except(['password', 'password_confirmation']))
                    ->withErrors(['company_id' => 'La empresa alcanzó el límite de usuarios de su plan.']);
            }
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'company_id' => $companyId,
            'role_id' => $validated['role_id'],
            'active' => true,
        ]);

        return redirect()->route('super-admin.users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Solo los roles de empresa necesitan una empresa asignada. El contador
     * trabaja sobre toda la plataforma, igual que el super admin, y va con
     * company_id nulo.
     */
    private function exigeEmpresa(Request $request): bool
    {
        $rol = Role::find($request->input('role_id'));

        return ! $rol || ! in_array($rol->name, self::ROLES_SIN_EMPRESA, true);
    }

    /**
     * Roles que este modulo NO puede asignar nunca, se envie lo que se envie.
     *
     * El desplegable ya no ofrece super_admin, pero la validacion solo miraba
     * 'exists:roles,id': bastaba con mandar ese id a mano para ascenderse. Es
     * el motivo por el que esto se comprueba en el servidor y no en la vista.
     */
    /** Roles que el usuario conectado puede llegar a asignar. */
    private function rolesAsignables()
    {
        $excluidos = auth()->user()->hasRole('super_admin')
            ? ['super_admin']
            : ['super_admin', 'contador'];

        return Role::whereNotIn('name', $excluidos)->get();
    }

    private function rolAsignable(Request $request): void
    {
        $rol = Role::find($request->input('role_id'));

        abort_if($rol && $rol->name === 'super_admin', 403,
            'El rol Super Admin no se asigna desde este modulo.');

        // Un contador solo da de alta usuarios de empresa: no puede crear otras
        // cuentas con acceso a toda la plataforma.
        abort_if($rol && $rol->name === 'contador' && ! auth()->user()->hasRole('super_admin'), 403,
            'Solo el Super Admin puede crear contadores.');
    }

    /**
     * Impide que quien no es Super Admin edite una cuenta de plataforma
     * (super admin u otro contador). Sin esto, un contador podria cambiar el
     * correo del dueno de la plataforma y quedarse con la cuenta.
     */
    private function puedeEditar(User $user): void
    {
        if (auth()->user()->hasRole('super_admin')) {
            return;
        }

        abort_if($user->role && in_array($user->role->name, self::ROLES_SIN_EMPRESA, true), 403,
            'No puedes editar cuentas de plataforma.');
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
        $this->puedeEditar($user);

        $companies = Company::orderBy('razon_social')->get();
        $roles = $this->rolesAsignables();

        if (request()->ajax() || request()->boolean('modal')) {
            return view('super-admin.users._form_modal', compact('user', 'companies', 'roles'));
        }

        return view('super-admin.users.edit', compact('user', 'companies', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->puedeEditar($user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'company_id' => [$this->exigeEmpresa($request) ? 'required' : 'nullable', 'exists:companies,id'],
            'role_id' => 'required|exists:roles,id',
        ], [], ['company_id' => 'empresa']);

        $this->rolAsignable($request);

        // El contador no pertenece a ninguna empresa (ver exigeEmpresa()).
        $validated['company_id'] = $this->exigeEmpresa($request) ? $validated['company_id'] : null;

        if ($validated['company_id'] && (int) $user->company_id !== (int) $validated['company_id']) {
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
