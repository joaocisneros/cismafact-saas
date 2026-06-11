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
        $apiKeys = ApiKey::where('company_id', Auth::user()->company_id)
            ->latest()
            ->get();

        return view('empresa.api-keys.index', compact('apiKeys'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $plainSecret = ApiKey::generateSecret();

        ApiKey::create([
            'company_id' => Auth::user()->company_id,
            'name' => $request->name,
            'key' => ApiKey::generateKey(),
            'secret' => Hash::make($plainSecret),
            'plain_secret' => $plainSecret,
            'abilities' => ['*'],
            'active' => true,
        ]);

        return back()->with('success', 'API Key creada. Guarda el Secret, solo se muestra una vez: ' . $plainSecret);
    }

    public function destroy(ApiKey $apiKey)
    {
        if ($apiKey->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $apiKey->delete();

        return back()->with('success', 'API Key eliminada.');
    }

    public function toggle(ApiKey $apiKey)
    {
        if ($apiKey->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $apiKey->update(['active' => !$apiKey->active]);

        $status = $apiKey->active ? 'activada' : 'desactivada';

        return back()->with('success', "API Key {$status}.");
    }

    public function showSecret(ApiKey $apiKey)
    {
        if ($apiKey->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        return response()->json([
            'key' => $apiKey->key,
            'secret' => $apiKey->plain_secret,
        ]);
    }

    public function regenerate(ApiKey $apiKey)
    {
        if ($apiKey->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $plainSecret = ApiKey::generateSecret();

        $apiKey->update([
            'secret' => Hash::make($plainSecret),
            'plain_secret' => $plainSecret,
        ]);

        return back()->with('success', 'Secret regenerado. Guarda el nuevo valor: ' . $plainSecret);
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
