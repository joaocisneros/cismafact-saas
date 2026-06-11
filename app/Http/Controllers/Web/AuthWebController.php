<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthWebController extends Controller
{
    /** Máximo de intentos fallidos antes de bloquear temporalmente. */
    private const MAX_ATTEMPTS = 5;

    /** Ventana de bloqueo en segundos. */
    private const DECAY_SECONDS = 60;

    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Anti fuerza bruta: bloquea tras varios intentos fallidos por email+IP.
        $key = $this->throttleKey($request);
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Demasiados intentos fallidos. Vuelve a intentar en {$seconds} segundos.",
            ]);
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            // Credenciales correctas: limpiar el contador de intentos.
            RateLimiter::clear($key);

            $user = Auth::user();

            if (!$user->active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Tu cuenta está desactivada.',
                ])->onlyInput('email');
            }

            if ($user->locked_until && $user->locked_until->isFuture()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Tu cuenta está bloqueada. Contacta al administrador.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            DB::table('login_history')->insert([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'success' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user->update(['last_login_at' => now()]);

            return redirect()->intended($this->getRedirectPath($user));
        }

        // Intento fallido: cuenta para el límite y se registra (si el email existe).
        RateLimiter::hit($key, self::DECAY_SECONDS);
        $this->recordFailedAttempt($request);

        throw ValidationException::withMessages([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ]);
    }

    /** Clave del limitador: por email + IP, para no afectar a otros usuarios. */
    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')) . '|' . $request->ip());
    }

    /** Registra el intento fallido en login_history si el email corresponde a un usuario. */
    private function recordFailedAttempt(Request $request): void
    {
        $userId = User::where('email', $request->input('email'))->value('id');

        if (! $userId) {
            return; // No se registra para emails inexistentes (evita ruido / enumeración).
        }

        DB::table('login_history')->insert([
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'success' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function getRedirectPath($user): string
    {
        if ($user->hasRole('super_admin')) {
            return route('super-admin.dashboard');
        }
        return route('empresa.dashboard');
    }
}
