<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetLinkController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            $token = Str::random(64);

            DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            try {
                Mail::raw("Para restablecer tu contraseña, haz clic en el siguiente enúmero: " . url('/reset-password/' . $token), function ($message) use ($request) {
                    $message->to($request->email)
                            ->subject('Restablecer contraseña - ' . config('app.name'));
                });
            } catch (\Exception $e) {
                // Email not configured, token still works via direct link
            }
        }

        return back()->with('status', 'Si el correo existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña.');
    }
}
