<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ApiUsage;
use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Credenciales de prueba para programadores externos.
 *
 * Vivía dentro de "Administración de API", pero son dos trabajos distintos:
 * allí se vigila el servicio (consumo, errores, logs) y aquí se reparte acceso
 * a quien va a integrar. Mezclarlos obligaba a bajar por una pantalla de
 * monitoreo para dar de alta a un dev.
 *
 * Los tokens cuelgan de la empresa marcada como demo, así que emiten contra
 * SUNAT beta: los comprobantes no tienen valor legal y no ensucian datos reales.
 */
class TokenPruebaController extends Controller
{
    public function index()
    {
        $empresaDemo = Company::where('es_demo', true)->first();

        $tokens = ApiKey::whereHas('company', fn ($q) => $q->where('es_demo', true))
            ->with('company:id,razon_social')
            ->latest()
            ->get();

        // Consumo por token, para ver quién está integrando de verdad.
        $consumo = ApiUsage::selectRaw('api_key_id, COUNT(*) as total')
            ->whereIn('api_key_id', $tokens->pluck('id'))
            ->groupBy('api_key_id')
            ->pluck('total', 'api_key_id');

        return view('super-admin.tokens-prueba.index', [
            'tokens' => $tokens,
            'empresaDemo' => $empresaDemo,
            'consumo' => $consumo,
        ]);
    }

    /**
     * Un secret nuevo para un token de prueba que ya existe.
     *
     * El secret no se puede releer, asi que un programador que lo pierda
     * —o un token de los que se crearon sin llegar a enseñarlo— no tiene
     * otra salida. La key no cambia: solo hay que pasarle el secret.
     */
    public function regenerar(ApiKey $apiKey)
    {
        if (! $apiKey->company?->es_demo) {
            return back()->with('error',
                'Esa credencial es de una empresa real, no de sandbox. Su secret se cambia desde '
                . 'API Facturación, que es donde se ve a quién afecta.');
        }

        $plainSecret = ApiKey::generateSecret();

        $apiKey->update(['secret' => $plainSecret]);

        Cache::forget('api_global_index');

        return back()->with('success', "Secret de «{$apiKey->name}» regenerado. El anterior ya no funciona.");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'dev_name' => [
                'required', 'string', 'max:120',
                /*
                 * Sin esto se podian crear tres tokens llamados «JOAO», y el
                 * nombre es lo unico que dice de quien es cada uno: si uno se
                 * filtra y hay que bloquearlo, no se sabe cual de los tres es.
                 *
                 * Solo estorban los que siguen sirviendo. Uno bloqueado o
                 * caducado ya no es de nadie, asi que su nombre se puede
                 * reutilizar.
                 */
                function (string $atributo, mixed $valor, Closure $fallar) {
                    $enUso = ApiKey::whereHas('company', fn ($q) => $q->where('es_demo', true))
                        ->where('name', 'Sandbox - ' . trim((string) $valor))
                        ->where('active', true)
                        ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                        ->exists();

                    if ($enUso) {
                        $fallar('Ya hay un token en uso con ese nombre. Ponle algo que lo distinga '
                            . '(por ejemplo «' . trim((string) $valor) . ' - app movil») o bloquea el anterior.');
                    }
                },
            ],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ], [], [
            'dev_name' => 'nombre del programador',
            'expires_in_days' => 'caducidad',
        ]);

        $demo = Company::where('es_demo', true)->first();

        if (! $demo) {
            return back()->with('error',
                'No hay ninguna empresa marcada como demo. Entra a Empresas, abre el detalle de una '
                . 'y márcala como demo: los tokens de prueba cuelgan de ella.');
        }

        $plainSecret = ApiKey::generateSecret();

        $apiKey = ApiKey::create([
            'company_id' => $demo->id,
            'name' => 'Sandbox - ' . $validated['dev_name'],
            'key' => ApiKey::generateKey(),
            'secret' => $plainSecret,
            'abilities' => ['*'],
            'active' => true,
            'expires_at' => isset($validated['expires_in_days'])
                ? now()->addDays((int) $validated['expires_in_days'])
                : null,
        ]);

        Cache::forget('api_global_index');

        // Para abrir su ficha nada mas crearlo: es donde estan las
        // credenciales que hay que copiar, y el siguiente paso siempre es
        // ese. Sin esto habia que buscar el token en la lista y abrirlo.
        session()->flash('abrir_ficha', $apiKey->id);
        session()->flash('abrir_ficha_nombre', $apiKey->name);

        return back()->with('success', "Token de «{$validated['dev_name']}» generado. Copia su Secret: no se vuelve a mostrar solo.");
    }
}
