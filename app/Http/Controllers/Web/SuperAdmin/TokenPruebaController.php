<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ApiUsage;
use App\Models\Company;
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
        $plainSecret = ApiKey::generateSecret();

        $apiKey->update(['secret' => Hash::make($plainSecret)]);

        Cache::forget('api_global_index');

        session()->put('credenciales_nuevas', [
            // La pantalla donde se enseña: sin esto el aviso se iba con el
            // usuario a la siguiente que visitara, y aparecia en un modulo
            // que no tiene nada que ver con la credencial que se genero.
            'pantalla' => 'super-admin.tokens-prueba.index',
            'nombre' => $apiKey->name,
            'key' => $apiKey->key,
            'secret' => $plainSecret,
        ]);

        return back()->with('success', "Secret de «{$apiKey->name}» regenerado. El anterior ya no funciona.");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'dev_name' => ['required', 'string', 'max:120'],
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
            'secret' => Hash::make($plainSecret),
            'abilities' => ['*'],
            'active' => true,
            'expires_at' => isset($validated['expires_in_days'])
                ? now()->addDays((int) $validated['expires_in_days'])
                : null,
        ]);

        Cache::forget('api_global_index');

        /*
         * El secreto viaja aqui porque es la unica vez que se puede ver.
         *
         * Antes se guardaba una copia descifrable y este mensaje mandaba a
         * leerla con «Ver». Al pasar el secret a hash esa copia dejo de
         * existir, y como aqui no se devolvia, el token salia sin que nadie
         * pudiera saber con que se usa: inservible desde el momento de
         * crearlo.
         *
         * En sesion y no en flash: el formulario va por fetch, y ese fetch
         * sigue el redirect y consume el flash antes de que la pagina se
         * recargue.
         */
        session()->put('credenciales_nuevas', [
            // La pantalla donde se enseña: sin esto el aviso se iba con el
            // usuario a la siguiente que visitara, y aparecia en un modulo
            // que no tiene nada que ver con la credencial que se genero.
            'pantalla' => 'super-admin.tokens-prueba.index',
            'nombre' => $apiKey->name,
            'key' => $apiKey->key,
            'secret' => $plainSecret,
        ]);

        return back()->with('success', "Token de «{$validated['dev_name']}» generado.");
    }
}
