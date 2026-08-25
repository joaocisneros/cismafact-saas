<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ApiKey;
use App\Models\Branch;
use App\Models\Correlative;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    public function showRegistrationForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'razon_social' => 'required|string|max:255',
            'ruc' => 'required|string|size:11|regex:/^\d{11}$/|unique:companies,ruc',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Todo cliente nuevo nace en el plan Free (igual que cuando lo crea el Super Admin).
        $freePlan = Plan::where('code', 'free')->first();

        DB::beginTransaction();

        try {
            $company = Company::create([
                'ruc' => $request->ruc,
                // Toda cuenta nueva emite desde la plataforma. Si alguien prefiere
                // el portal de SUNAT, lo cambia luego en Configuracion.
                'metodo_emision' => 'cisma_fact',
                'razon_social' => $request->razon_social,
                'nombre_comercial' => $request->razon_social,
                'direccion' => '',
                'ubigeo' => '150101',
                'distrito' => 'Lima',
                'provincia' => 'Lima',
                'departamento' => 'Lima',
                'email' => $request->email,
                // Credenciales de prueba de SUNAT: son fijas y publicas, iguales
                // para todos. Se dejan puestas para que la empresa pueda emitir
                // en beta nada mas registrarse, sin configurar nada. Al pasar a
                // produccion se le piden las suyas reales.
                'usuario_sol' => 'MODDATOS',
                'clave_sol' => 'moddatos',
                'endpoint_beta' => 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService',
                'endpoint_produccion' => 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService',
                'plan_id' => $freePlan?->id,
                'activo' => true,
            ]);

            $branch = Branch::create([
                'company_id' => $company->id,
                // SUNAT identifica el domicilio fiscal con el codigo 0000; los
                // anexos van 0001, 0002... Con '001' el XML llevaba un codigo de
                // establecimiento que SUNAT no reconoce y en produccion lo rechaza.
                'codigo' => '0000',
                'nombre' => 'Principal',
                'direccion' => '',
                'ubigeo' => '150101',
                'distrito' => 'Lima',
                'provincia' => 'Lima',
                'departamento' => 'Lima',
                'activo' => true,
            ]);

            $documentTypes = [
                ['tipo_documento' => '01', 'serie' => 'F001'],
                ['tipo_documento' => '01', 'serie' => 'F002'],
                ['tipo_documento' => '03', 'serie' => 'B001'],
                ['tipo_documento' => '03', 'serie' => 'B002'],
                ['tipo_documento' => '07', 'serie' => 'FC01'],
                ['tipo_documento' => '07', 'serie' => 'BC01'],
                ['tipo_documento' => '08', 'serie' => 'FD01'],
                ['tipo_documento' => '08', 'serie' => 'BD01'],
                ['tipo_documento' => '09', 'serie' => 'T001'],
            ];

            foreach ($documentTypes as $dt) {
                Correlative::create([
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'tipo_documento' => $dt['tipo_documento'],
                    'serie' => $dt['serie'],
                    'correlativo_actual' => 0,
                ]);
            }

            $companyAdminRole = Role::where('name', 'company_admin')->first();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $companyAdminRole?->id,
                'company_id' => $company->id,
                'user_type' => 'user',
                'active' => true,
                'email_verified_at' => now(),
            ]);

            ApiKey::create([
                'company_id' => $company->id,
                'name' => 'API Principal',
                'key' => ApiKey::generateKey(),
                'secret' => Hash::make($secret = ApiKey::generateSecret()),
                'abilities' => ['*'],
                'active' => true,
            ]);

            // Suscripcion Free activa: deja al cliente listo para que luego se le suba de plan.
            if ($freePlan) {
                Subscription::create([
                    'company_id' => $company->id,
                    'plan_id' => $freePlan->id,
                    'status' => 'active',
                    'starts_at' => now()->toDateString(),
                    'monthly_price' => $freePlan->monthly_price,
                    'auto_renew' => false,
                ]);
            }

            DB::commit();

            Auth::login($user);

            return redirect()->route('dashboard')->with('success', '¡Cuenta creada exitosamente! Configura tu empresa para comenzar.');

        } catch (\Exception $e) {
            DB::rollBack();

            // El detalle va al log; al visitante solo un mensaje generico, para
            // no filtrar la estructura de la base ni rutas internas.
            Log::error('Fallo el registro publico de empresa', [
                'ruc' => $request->ruc,
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput($request->only('razon_social', 'ruc', 'email'))
                         ->withErrors(['email' => 'No se pudo crear la cuenta. Intenta nuevamente o escribe a soporte.']);
        }
    }
}
