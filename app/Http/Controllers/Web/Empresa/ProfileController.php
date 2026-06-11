<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function edit()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return view('empresa.profile.edit', compact('user'));
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
        ]);

        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta.']);
            }

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'password_changed_at' => now(),
            ]);
        } else {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);
        }

        return back()->with('success', 'Perfil actualizado exitosamente.');
    }

    public function security()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $loginHistory = DB::table('login_history')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        return view('empresa.profile.security', compact('user', 'loginHistory'));
    }

    public function toggleTwoFactor(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->update([
            'two_factor_enabled' => !$user->two_factor_enabled,
        ]);

        $status = $user->two_factor_enabled ? 'activada' : 'desactivada';

        return back()->with('success', "Autenticación de dos factores {$status}.");
    }
}
