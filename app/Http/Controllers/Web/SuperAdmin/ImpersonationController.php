<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use App\Support\Impersonation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Soporte: permite al Super Admin entrar al panel de una empresa como su
 * administrador, con acceso completo, y volver luego a su propia cuenta.
 *
 * Cada entrada y cada salida quedan registradas en audit_logs.
 */
class ImpersonationController extends Controller
{
    /** Entra al panel de la empresa como su administrador. */
    public function start(Request $request, Company $company)
    {
        $superAdmin = $request->user();

        // Nunca anidar suplantaciones: se perdería el rastro de vuelta.
        if (Impersonation::activa()) {
            return back()->with('error', 'Ya tienes una sesión de soporte abierta. Ciérrala antes de abrir otra.');
        }

        $objetivo = $this->adminDeLaEmpresa($company);

        if (! $objetivo) {
            return back()->with('error', "La empresa {$company->razon_social} no tiene un usuario administrador activo al cual entrar.");
        }

        $this->registrar($request, 'impersonate_start', $company, $superAdmin->id, [
            'empresa' => $company->razon_social,
            'ruc' => $company->ruc,
            'usuario_destino_id' => $objetivo->id,
            'usuario_destino_email' => $objetivo->email,
        ]);

        $this->cambiarDeUsuarioConservandoElToken($request, $objetivo);

        Impersonation::iniciar($superAdmin);

        return redirect()->route('empresa.dashboard')
            ->with('success', "Entraste como soporte a {$company->razon_social}. Recuerda salir cuando termines.");
    }

    /** Vuelve a la cuenta del Super Admin. */
    public function stop(Request $request)
    {
        if (! Impersonation::activa()) {
            return redirect()->route('dashboard');
        }

        $superAdmin = User::find(Impersonation::idSuplantador());
        $suplantado = $request->user();
        $company = $suplantado?->company;

        Impersonation::terminar();

        // Si el Super Admin ya no existe o fue desactivado, se cierra todo:
        // dejar la sesión con el usuario de la empresa sería un acceso colgado.
        if (! $superAdmin || ! $superAdmin->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Tu cuenta de Super Admin ya no está disponible. Vuelve a iniciar sesión.']);
        }

        $this->cambiarDeUsuarioConservandoElToken($request, $superAdmin);

        $this->registrar($request, 'impersonate_stop', $company, $superAdmin->id, [
            'empresa' => $company?->razon_social,
            'ruc' => $company?->ruc,
            'usuario_destino_id' => $suplantado?->id,
        ]);

        // Se vuelve al listado y no a la ficha: es la pantalla desde la que se
        // suele saltar a otra empresa, y al llegar recién generada trae un
        // token CSRF válido (el anterior murió al cambiar de usuario).
        $nombre = $company?->razon_social;

        return redirect()->route('super-admin.companies.index')
            ->with('success', $nombre
                ? "Saliste de la sesión de soporte de {$nombre} y volviste a tu cuenta."
                : 'Saliste de la sesión de soporte y volviste a tu cuenta.');
    }

    /**
     * Cambia el usuario autenticado dejando intacto el token CSRF.
     *
     * Auth::login() llama por dentro a session()->regenerate(true), que además
     * de rotar el id de sesión genera un '_token' nuevo. Eso deja obsoleta
     * cualquier pantalla que el navegador ya tuviera abierta (el listado de
     * empresas al que se vuelve con "Atrás"), y el siguiente clic responde 419
     * "Página expirada" en lugar de entrar. Como es la misma persona y el mismo
     * navegador a ambos lados del cambio, se conserva el token: el id de sesión
     * sí se renueva, que es lo que protege de la fijación de sesión.
     */
    private function cambiarDeUsuarioConservandoElToken(Request $request, User $usuario): void
    {
        $token = $request->session()->token();

        Auth::login($usuario);

        $request->session()->put('_token', $token);
    }

    /**
     * Administrador activo de la empresa. Se prefiere company_admin; si no hay,
     * se toma cualquier usuario activo de la empresa para no dejar sin soporte
     * a cuentas creadas a mano.
     */
    private function adminDeLaEmpresa(Company $company): ?User
    {
        $base = User::where('company_id', $company->id)->where('active', true);

        return (clone $base)
                ->whereHas('role', fn ($q) => $q->where('name', 'company_admin'))
                ->orderBy('id')
                ->first()
            ?? $base->orderBy('id')->first();
    }

    /** Deja constancia en audit_logs de la entrada o salida de soporte. */
    private function registrar(Request $request, string $accion, ?Company $company, int $superAdminId, array $data): void
    {
        try {
            AuditLog::create([
                'user_id' => $superAdminId,
                'company_id' => $company?->id,
                'action' => $accion,
                'method' => $request->method(),
                'route_name' => $request->route()?->getName(),
                'path' => $request->path(),
                'subject_type' => $company ? 'Company' : null,
                'subject_id' => $company?->id,
                'description' => $accion === 'impersonate_start'
                    ? 'Super Admin entró como soporte a la empresa'
                    : 'Super Admin salió de la sesión de soporte',
                'data' => $data,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'response_status' => 302,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
