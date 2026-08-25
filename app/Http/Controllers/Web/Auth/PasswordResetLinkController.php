<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

            $enlace = url('/reset-password/' . $token);

            $cuerpo = "Recibimos una solicitud para restablecer tu contraseña en "
                . config('app.name') . ".\n\n"
                . "Entra en este enlace para elegir una nueva:\n{$enlace}\n\n"
                . "Si no fuiste tú, ignora este mensaje: tu contraseña seguirá siendo la misma.";

            try {
                Mail::raw($cuerpo, function ($message) use ($request) {
                    $message->to($request->email)
                            ->subject('Restablecer contraseña - ' . config('app.name'));
                });
            } catch (\Exception $e) {
                // El token sigue siendo válido, asi que al usuario no se le corta el
                // camino; pero el fallo se registra; antes se descartaba en silencio y
                // un SMTP mal configurado no dejaba ni rastro.
                Log::error('No se pudo enviar el correo de recuperacion', [
                    'email' => $request->email,
                    'motivo' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('status', 'Si el correo existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña.');
    }
}
