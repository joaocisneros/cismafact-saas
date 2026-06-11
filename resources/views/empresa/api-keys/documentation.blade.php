@extends('layouts.app')

@section('title', 'Documentación API')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Documentación API</h1>
            <p class="text-gray-500 mt-1">Endpoints disponibles para integración</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('empresa.api-keys.postman') }}" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 text-sm">
                Descargar Postman
            </a>
            <a href="{{ route('empresa.api-keys.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                Volver
            </a>
        </div>
    </div>

    @if($apiKey)
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Autenticación</h2>
        <p class="text-sm text-gray-600 mb-3">Todas las peticiones deben incluir el header:</p>
        <div class="bg-gray-900 text-green-400 rounded-lg p-4 font-mono text-sm">
            Authorization: Bearer {tu_api_token}
        </div>
        <p class="text-xs text-gray-500 mt-2">Obtén tu token haciendo POST a <code>/api/auth/login</code></p>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Ejemplos de Código</h2>

        <div x-data="{ tab: 'php' }" class="space-y-4">
            <div class="flex gap-2 border-b pb-2">
                <button @click="tab = 'php'" :class="tab === 'php' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'" class="px-4 py-2 rounded-lg text-sm font-medium transition">PHP</button>
                <button @click="tab = 'laravel'" :class="tab === 'laravel' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'" class="px-4 py-2 rounded-lg text-sm font-medium transition">Laravel</button>
                <button @click="tab = 'javascript'" :class="tab === 'javascript' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'" class="px-4 py-2 rounded-lg text-sm font-medium transition">JavaScript</button>
            </div>

            <div x-show="tab === 'php'" class="bg-gray-900 text-green-400 rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre>&lt;?php
$apiKey = 'TU_API_KEY';
$apiSecret = 'TU_API_SECRET';
$baseUrl = 'http://localhost:8090/api';

// Crear factura
$invoice = [
    'tipo_documento' => '01',
    'serie' => 'F001',
    'correlativo' => 1,
    'fecha_emision' => date('Y-m-d'),
    'cliente_tipo_documento' => '6',
    'cliente_numero_documento' => '20512345679',
    'cliente_razon_social' => 'Empresa S.A.C.',
    'items' => [
        [
            'cod_producto' => '001',
            'unidad' => 'NIU',
            'descripcion' => 'Producto de prueba',
            'cantidad' => 1,
            'mto_unitario' => 100.00,
            'mto_valor_venta' => 100.00,
            'igv' => 18.00,
            'total' => 118.00,
        ]
    ],
    'mto_imp_venta' => 118.00,
];

$response = $client->post($baseUrl . '/invoices', [
    'headers' => [
        'Authorization' => 'Bearer ' . $apiKey,
        'X-Api-Secret' => $apiSecret,
        'Content-Type' => 'application/json',
    ],
    'json' => $invoice,
]);
echo $response->getBody();</pre>
            </div>

            <div x-show="tab === 'laravel'" class="bg-gray-900 text-green-400 rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre>&lt;?php
// En tu controlador o servicio
use Illuminate\Support\Facades\Http;

$apiKey = config('services.cismofact.api_key');
$apiSecret = config('services.cismofact.api_secret');
$baseUrl = config('services.cismofact.base_url');

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'X-Api-Secret' => $apiSecret,
])->post($baseUrl . '/invoices', [
    'tipo_documento' => '01',
    'serie' => 'F001',
    'correlativo' => 1,
    'fecha_emision' => now()->format('Y-m-d'),
    'cliente_tipo_documento' => '6',
    'cliente_numero_documento' => '20512345679',
    'cliente_razon_social' => 'Empresa S.A.C.',
    'items' => [
        [
            'cod_producto' => '001',
            'unidad' => 'NIU',
            'descripcion' => 'Producto',
            'cantidad' => 1,
            'mto_unitario' => 100.00,
            'igv' => 18.00,
            'total' => 118.00,
        ]
    ],
    'mto_imp_venta' => 118.00,
]);

return $response->json();</pre>
            </div>

            <div x-show="tab === 'javascript'" class="bg-gray-900 text-green-400 rounded-lg p-4 font-mono text-xs overflow-x-auto">
<pre>const apiKey = 'TU_API_KEY';
const apiSecret = 'TU_API_SECRET';
const baseUrl = 'http://localhost:8090/api';

const response = await fetch(`${baseUrl}/invoices`, {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${apiKey}`,
        'X-Api-Secret': apiSecret,
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        tipo_documento: '01',
        serie: 'F001',
        correlativo: 1,
        fecha_emision: new Date().toISOString().split('T')[0],
        cliente_tipo_documento: '6',
        cliente_numero_documento: '20512345679',
        cliente_razon_social: 'Empresa S.A.C.',
        items: [{
            cod_producto: '001',
            unidad: 'NIU',
            descripcion: 'Producto',
            cantidad: 1,
            mto_unitario: 100.00,
            igv: 18.00,
            total: 118.00,
        }],
        mto_imp_venta: 118.00,
    }),
});

const data = await response.json();
console.log(data);</pre>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Endpoints Disponibles</h2>

        <div class="space-y-4">
            <div class="border rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-bold">POST</span>
                    <span class="font-mono text-sm">/api/auth/login</span>
                </div>
                <p class="text-sm text-gray-600">Autenticar usuario y obtener token de acceso.</p>
            </div>

            <div class="border rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold">POST</span>
                    <span class="font-mono text-sm">/api/invoices</span>
                </div>
                <p class="text-sm text-gray-600">Crear una nueva factura electrónica.</p>
            </div>

            <div class="border rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-bold">GET</span>
                    <span class="font-mono text-sm">/api/invoices</span>
                </div>
                <p class="text-sm text-gray-600">Listar facturas de la empresa.</p>
            </div>

            <div class="border rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold">POST</span>
                    <span class="font-mono text-sm">/api/boletas</span>
                </div>
                <p class="text-sm text-gray-600">Crear una nueva boleta de venta electrónica.</p>
            </div>

            <div class="border rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-bold">GET</span>
                    <span class="font-mono text-sm">/api/boletas</span>
                </div>
                <p class="text-sm text-gray-600">Listar boletas de la empresa.</p>
            </div>

            <div class="border rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold">POST</span>
                    <span class="font-mono text-sm">/api/credit-notes</span>
                </div>
                <p class="text-sm text-gray-600">Crear una nota de crédito electrónica.</p>
            </div>

            <div class="border rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold">POST</span>
                    <span class="font-mono text-sm">/api/debit-notes</span>
                </div>
                <p class="text-sm text-gray-600">Crear una nota de débito electrónica.</p>
            </div>

            <div class="border rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold">POST</span>
                    <span class="font-mono text-sm">/api/retentions</span>
                </div>
                <p class="text-sm text-gray-600">Crear un comprobante de retención.</p>
            </div>

            <div class="border rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold">POST</span>
                    <span class="font-mono text-sm">/api/dispatch-guides</span>
                </div>
                <p class="text-sm text-gray-600">Crear una guía de remisión electrónica.</p>
            </div>

            <div class="border rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold">POST</span>
                    <span class="font-mono text-sm">/api/daily-summaries</span>
                </div>
                <p class="text-sm text-gray-600">Enviar resumen diario de documentos.</p>
            </div>

            <div class="border rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold">POST</span>
                    <span class="font-mono text-sm">/api/voided-documents</span>
                </div>
                <p class="text-sm text-gray-600">Anular un documento electrónico.</p>
            </div>
        </div>
    </div>
</div>
@endsection
