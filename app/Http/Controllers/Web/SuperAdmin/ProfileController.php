<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Perfil del Super Admin: nombre, correo y contraseña propios.
 *
 * El panel de empresa ya tenia su equivalente; el de Super Admin no, asi que
 * no habia forma de cambiarse la contraseña desde dentro.
 */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Desde el menu del avatar se pide en modal; la pagina completa sigue
        // existiendo por si se entra directo a la URL.
        if ($request->ajax() || $request->boolean('modal')) {
            return view('partials.perfil-modal', [
                'user' => $user,
                'rutaUpdate' => 'super-admin.profile.update',
            ]);
        }

        $loginHistory = DB::table('login_history')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('super-admin.profile.edit', compact('user', 'loginHistory'));
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'current_password' => 'required_with:password',
            'password' => 'nullable|confirmed|min:8',
        ], [
            'current_password.required_with' => 'Para cambiar la contraseña debes escribir la actual.',
            'password.confirmed' => 'La nueva contraseña y su confirmación no coinciden.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
        ]);

        $datos = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                // ValidationException y no back()->withErrors(): desde el modal la
                // peticion va por fetch, y un redirect se leeria como "guardado".
                // Asi Laravel responde 422 y el modal enseña el error.
                throw ValidationException::withMessages([
                    'current_password' => 'La contraseña actual es incorrecta.',
                ]);
            }

            $datos['password'] = Hash::make($request->password);
            $datos['password_changed_at'] = now();
        }

        $user->update($datos);

        return back()->with('success', $request->filled('password')
            ? 'Perfil y contraseña actualizados.'
            : 'Perfil actualizado.');
    }
}
