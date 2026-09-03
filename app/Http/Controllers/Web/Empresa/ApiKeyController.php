<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;

class ApiKeyController extends Controller
{
    public function index()
    {
        // Solo las suyas. Los tokens que el Super Admin reparte a
        // programadores cuelgan de la empresa demo, asi que a su dueño le
        // salian aqui mezclados con los propios y podia regenerarlos o
        // borrarlos, dejando sin servicio al programador.
        $apiKeys = ApiKey::where('company_id', Auth::user()->company_id)
            ->where('origen', 'empresa')
            ->latest()
            ->get();

        return view('empresa.api-keys.index', compact('apiKeys'));
    }

    /** Formulario de alta, para abrirlo en el modal del panel. */
    public function create()
    {
        return view('empresa.api-keys._form_modal');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $plainSecret = ApiKey::generateSecret();

        $apiKey = ApiKey::create([
            'company_id' => Auth::user()->company_id,
            'name' => $request->name,
            'key' => ApiKey::generateKey(),
            'secret' => $plainSecret,
            'abilities' => ['*'],
            'active' => true,
        ]);

        /*
         * El secreto viaja aqui porque es la unica vez que se va a ver.
         *
         * Antes no se devolvia: se guardaba una copia descifrable y se leia
         * cuando hiciera falta. Eso hacia comodo perderlo, y a cambio dejaba el
         * secreto de cada cliente recuperable por cualquiera que alcanzara la
         * base y la APP_KEY, que viven en el mismo servidor. Guardado como hash
         * no se puede leer ni aunque se roben las dos.
         */
        return back()->with('success', "API Key «{$request->name}» creada.");
    }

    public function destroy(ApiKey $apiKey)
    {
        if ($apiKey->origen !== 'empresa') {
            abort(404);
        }

        if ($apiKey->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $apiKey->delete();

        return back()->with('success', 'API Key eliminada.');
    }

    public function toggle(ApiKey $apiKey)
    {
        if ($apiKey->origen !== 'empresa') {
            abort(404);
        }

        if ($apiKey->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $apiKey->update(['active' => !$apiKey->active]);

        $status = $apiKey->active ? 'activada' : 'desactivada';

        return back()->with('success', "API Key {$status}.");
    }

    /**
     * El secret de una credencial, solo cuando se pide.
     *
     * No viaja con la pagina: con varias credenciales saldrian todos los
     * secretos en cada carga, a la vista de quien mire el codigo fuente o
     * pase por delante de la pantalla.
     */
    public function showSecret(ApiKey $apiKey)
    {
        if ($apiKey->origen !== 'empresa') {
            abort(404);
        }

        // Cada empresa solo ve lo suyo.
        if ($apiKey->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        return response()->json(['secret' => $apiKey->secret]);
    }

    public function regenerate(ApiKey $apiKey)
    {
        if ($apiKey->origen !== 'empresa') {
            abort(404);
        }

        if ($apiKey->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $plainSecret = ApiKey::generateSecret();

        $apiKey->update(['secret' => $plainSecret]);

        // La key no cambia: quien la use solo tiene que cambiar el secret.
        return back()->with('success', "Secret de «{$apiKey->name}» regenerado. El anterior dejó de funcionar.");
    }

    public function documentation()
    {
        $apiKey = ApiKey::where('company_id', Auth::user()->company_id)->first();

        return view('empresa.api-keys.documentation', compact('apiKey'));
    }

    public function postman()
    {
        $apiKey = ApiKey::where('company_id', Auth::user()->company_id)->first();
        $baseUrl = config('app.url', 'http://localhost:8090') . '/api';

        $collection = [
            'info' => [
                'name' => 'Cisma Fact API',
                'description' => 'API de Facturación Electrónica SUNAT - Cisma Fact',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [
                    ['key' => 'token', 'value' => '{{api_token}}'],
                ],
            ],
            'variable' => [
                ['key' => 'base_url', 'value' => $baseUrl],
                ['key' => 'api_key', 'value' => $apiKey->key ?? 'YOUR_API_KEY'],
            ],
            'item' => [
                [
                    'name' => 'Auth',
                    'item' => [
                        [
                            'name' => 'Login',
                            'request' => [
                                'method' => 'POST',
                                'url' => '{{base_url}}/auth/login',
                                'body' => ['mode' => 'raw', 'raw' => json_encode(['email' => 'email@example.com', 'password' => 'password'])],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Facturas',
                    'item' => [
                        [
                            'name' => 'Crear Factura',
                            'request' => ['method' => 'POST', 'url' => '{{base_url}}/invoices'],
                        ],
                        [
                            'name' => 'Listar Facturas',
                            'request' => ['method' => 'GET', 'url' => '{{base_url}}/invoices'],
                        ],
                    ],
                ],
                [
                    'name' => 'Boletas',
                    'item' => [
                        [
                            'name' => 'Crear Boleta',
                            'request' => ['method' => 'POST', 'url' => '{{base_url}}/boletas'],
                        ],
                        [
                            'name' => 'Listar Boletas',
                            'request' => ['method' => 'GET', 'url' => '{{base_url}}/boletas'],
                        ],
                    ],
                ],
            ],
        ];

        return Response::json($collection)
            ->header('Content-Disposition', 'attachment; filename="CismaFact_API_Postman.json"');
    }
}
