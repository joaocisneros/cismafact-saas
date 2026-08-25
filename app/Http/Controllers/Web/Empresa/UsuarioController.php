<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\PlanLimitService;
use App\Support\Impersonation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * El dueño de la empresa gestiona su propio equipo.
 *
 * Todo lo de aquí está encerrado en su empresa: las consultas filtran por
 * company_id y el rol se elige de una lista fija, para que no se pueda crear
 * un usuario en otra empresa ni con un rol de plataforma.
 */
class UsuarioController extends Controller
{
    /** Roles que un dueño de empresa puede repartir dentro de su empresa. */
    private const ROLES_DE_EMPRESA = ['company_admin', 'company_user'];

    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $usuarios = User::where('company_id', $companyId)
            ->with('role:id,name,display_name')
            ->when($request->string('search')->trim()->toString(), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('empresa.usuarios.index', [
            'usuarios' => $usuarios,
            'cupo' => $this->cupo(),
        ]);
    }

    public function create()
    {
        $cupo = $this->cupo();

        if ($cupo['lleno']) {
            return $this->respuestaCupoLleno();
        }

        return $this->formulario(null);
    }

    public function store(Request $request)
    {
        if ($this->cupo()['lleno']) {
            return $this->respuestaCupoLleno();
        }

        $datos = $this->validar($request);

        User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => Hash::make($datos['password']),
            'company_id' => Auth::user()->company_id,
            'role_id' => $this->idDelRol($datos['rol']),
            'active' => true,
        ]);

        return redirect()->route('empresa.usuarios.index')
            ->with('success', "Usuario {$datos['name']} creado correctamente.");
    }

    public function edit(User $usuario)
    {
        $this->autorizar($usuario);

        return $this->formulario($usuario);
    }

    public function update(Request $request, User $usuario)
    {
        $this->autorizar($usuario);

        $datos = $this->validar($request, $usuario);

        $usuario->update([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'role_id' => $this->idDelRol($datos['rol']),
        ]);

        if (! empty($datos['password'])) {
            $usuario->update(['password' => Hash::make($datos['password'])]);
        }

        return redirect()->route('empresa.usuarios.index')
            ->with('success', "Usuario {$usuario->name} actualizado.");
    }

    /** Apaga o enciende la cuenta de un empleado. No se elimina nada. */
    public function toggleActive(User $usuario)
    {
        $this->autorizar($usuario);

        // Reactivar consume cupo: si el plan ya está lleno, no se puede.
        if (! $usuario->active && $this->cupo()['lleno']) {
            return back()->with('error', 'Alcanzaste el límite de usuarios de tu plan. Desactiva otro antes de reactivar este.');
        }

        $usuario->update(['active' => ! $usuario->active]);

        return back()->with('success', $usuario->active
            ? "Usuario {$usuario->name} activado."
            : "Usuario {$usuario->name} desactivado. Ya no podrá iniciar sesión.");
    }

    /**
     * Comprueba que el usuario sea de esta empresa y que no sea uno mismo.
     *
     * Editarse a si mismo desde aqui permitiria al dueño degradarse a empleado
     * y quedarse sin poder volver a entrar a esta pantalla. Su propio perfil lo
     * cambia desde el menu del avatar.
     */
    private function autorizar(User $usuario): void
    {
        abort_unless($usuario->company_id === Auth::user()->company_id, 403,
            'Ese usuario no pertenece a tu empresa.');

        abort_if($usuario->id === Auth::id(), 403,
            'Tu propia cuenta se edita desde «Mi perfil».');

        // Durante una sesión de soporte el usuario conectado es el dueño, así
        // que la comprobación anterior ya lo cubre.
    }

    /** Cuántos usuarios usa la empresa y cuántos le permite su plan. */
    private function cupo(): array
    {
        $company = Auth::user()->company;
        $limite = $company->plan?->user_limit;
        $usados = app(PlanLimitService::class)->usersUsed($company);

        return [
            'usados' => $usados,
            'limite' => $limite,
            'plan' => $company->plan?->name,
            'lleno' => $limite !== null && $limite > 0 && $usados >= $limite,
        ];
    }

    private function respuestaCupoLleno()
    {
        $cupo = $this->cupo();

        return redirect()->route('empresa.usuarios.index')->with('error',
            "Tu plan {$cupo['plan']} permite {$cupo['limite']} usuario(s) y ya los tienes en uso. "
            . 'Desactiva uno o cambia de plan para agregar más.');
    }

    private function formulario(?User $usuario)
    {
        $datos = [
            'usuario' => $usuario,
            'roles' => Role::whereIn('name', self::ROLES_DE_EMPRESA)->orderBy('id')->get(),
            'cupo' => $this->cupo(),
        ];

        if (request()->ajax() || request()->boolean('modal')) {
            return view('empresa.usuarios._form', $datos + ['modal' => true]);
        }

        return view('empresa.usuarios.form', $datos);
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'rol' => ['required', Rule::in(self::ROLES_DE_EMPRESA)],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ], [], [
            'name' => 'nombre',
            'email' => 'correo',
            'rol' => 'rol',
            'password' => 'contraseña',
        ]);
    }

    private function idDelRol(string $nombre): int
    {
        abort_unless(in_array($nombre, self::ROLES_DE_EMPRESA, true), 403);

        return Role::where('name', $nombre)->value('id');
    }
}
