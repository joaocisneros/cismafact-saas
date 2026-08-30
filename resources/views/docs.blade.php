<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentación API — Cisma Fact</title>
    <meta name="description" content="Documentación de la API de Cisma Fact: autenticación, endpoints y ejemplos para emitir comprobantes electrónicos a SUNAT.">
    <link rel="icon" href="{{ config('platform.favicon_url', asset('assets/brand/favicon.png')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-800">

    {{-- Header --}}
    <header class="border-b border-gray-100 sticky top-0 bg-white/90 backdrop-blur z-10">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('landing') }}" class="flex items-center">
                <img src="{{ config('platform.logo_url', asset('assets/brand/cisma-fact.png')) }}" alt="Cisma Fact" class="h-10 w-auto">
            </a>
            <nav class="flex items-center gap-2 sm:gap-3 text-sm">
                <a href="{{ route('landing') }}" class="px-3 py-2 font-medium text-gray-600 hover:text-blue-600">Inicio</a>
                <a href="{{ route('docs.consultas') }}" class="hidden px-3 py-2 font-medium text-gray-600 hover:text-blue-600 sm:inline-block">API RUC y DNI</a>
                <a href="{{ route('login') }}" class="px-3 py-2 font-medium text-gray-700 hover:text-blue-600">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="px-4 py-2 font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700">Crear cuenta</a>
            </nav>
        </div>
    </header>

    <div class="max-w-5xl mx-auto px-6 py-10 grid grid-cols-1 lg:grid-cols-[200px_1fr] gap-10">

        {{-- Índice lateral --}}
        <aside class="hidden lg:block">
            <nav class="sticky top-24 space-y-1 text-sm">
                <p class="px-2 text-xs font-semibold uppercase text-gray-400">Contenido</p>
                <a href="#introduccion" class="block rounded px-2 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-blue-600">1. Introducción</a>
                <a href="#autenticacion" class="block rounded px-2 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-blue-600">2. Autenticación</a>
                <a href="#endpoints" class="block rounded px-2 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-blue-600">3. Endpoints</a>
                <a href="#ejemplos" class="block rounded px-2 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-blue-600">4. Ejemplos de código</a>
            </nav>
        </aside>

        <main class="min-w-0 space-y-12">

            <div>
                <h1 class="text-3xl font-bold text-gray-900">Documentación de la API</h1>
                <p class="mt-2 text-gray-600">Integra la facturación electrónica de Cisma Fact en tu sistema, ERP o tienda online. Emite facturas, boletas, notas y guías directo a SUNAT desde tu código.</p>
            </div>

            {{-- 1. INTRODUCCIÓN --}}
            <section id="introduccion" class="scroll-mt-24 space-y-3">
                <h2 class="text-xl font-semibold text-gray-900">1. Introducción</h2>
                <p class="text-gray-600">La API es REST y responde en JSON. Toda petición se hace sobre la URL base:</p>
                <div class="rounded-lg bg-gray-900 px-4 py-3 font-mono text-sm text-gray-100">{{ url('/api') }}</div>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    <strong>Funciona con cualquier lenguaje de programación.</strong> Al ser una API REST sobre HTTP con JSON, la puedes consumir desde
                    <span class="font-medium">PHP, JavaScript/Node.js, Python, Java, C#/.NET, Ruby, Go</span> y cualquier otro lenguaje que pueda hacer peticiones HTTP. No necesitas instalar nada especial: solo enviar las cabeceras de autenticación.
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <strong>Ambientes:</strong> con un <strong>token de pruebas (sandbox)</strong> emites a <strong>SUNAT beta</strong> (documentos sin valor legal) para probar sin riesgo. En producción, con tu propio certificado y RUC, los comprobantes son reales ante SUNAT.
                </div>
            </section>

            {{-- 2. AUTENTICACIÓN --}}
            <section id="autenticacion" class="scroll-mt-24 space-y-3">
                <h2 class="text-xl font-semibold text-gray-900">2. Autenticación</h2>
                <p class="text-gray-600">Cada petición debe enviar tus credenciales en las cabeceras (headers). No hay login ni tokens que expiran por sesión: usas siempre estos dos valores.</p>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            <tr><td class="px-4 py-3 font-mono text-gray-700 w-48">X-Api-Key</td><td class="px-4 py-3 text-gray-600">Identifica tu cuenta. Te lo entrega Cisma Fact.</td></tr>
                            <tr><td class="px-4 py-3 font-mono text-gray-700">X-Api-Secret</td><td class="px-4 py-3 text-gray-600">Tu clave secreta. No la compartas públicamente.</td></tr>
                            <tr><td class="px-4 py-3 font-mono text-gray-700">Content-Type</td><td class="px-4 py-3 text-gray-600"><span class="font-mono">application/json</span> al enviar datos (POST).</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-gray-600">Para verificar que tus credenciales funcionan, prueba este endpoint:</p>
                <div class="rounded-lg bg-gray-900 px-4 py-3 font-mono text-sm text-gray-100 overflow-x-auto">
                    <span class="text-green-400">GET</span> {{ url('/api') }}/empresa
                </div>
            </section>

            {{-- 3. ENDPOINTS --}}
            <section id="endpoints" class="scroll-mt-24 space-y-4">
                <h2 class="text-xl font-semibold text-gray-900">3. Endpoints disponibles</h2>
                <p class="text-gray-600">Rutas relativas a la URL base. Cada tipo de comprobante sigue el mismo patrón.</p>

                @php
                    $grupos = [
                        'Conexión' => [
                            ['GET', '/empresa', 'Probar credenciales y ver datos de la empresa'],
                        ],
                        'Boletas' => [
                            ['GET', '/boletas', 'Listar boletas'],
                            ['POST', '/boletas', 'Emitir una boleta'],
                            ['GET', '/boletas/{id}', 'Ver una boleta'],
                            ['POST', '/boletas/{id}/send-sunat', 'Reenviar a SUNAT'],
                            ['GET', '/boletas/{id}/download-pdf', 'Descargar PDF'],
                            ['GET', '/boletas/{id}/download-xml', 'Descargar XML firmado'],
                            ['GET', '/boletas/{id}/download-cdr', 'Descargar CDR de SUNAT'],
                        ],
                        'Facturas' => [
                            ['GET', '/facturas', 'Listar facturas'],
                            ['POST', '/facturas', 'Emitir una factura'],
                            ['GET', '/facturas/{id}', 'Ver / descargar (mismo patrón que boletas)'],
                        ],
                        'Notas de crédito / débito' => [
                            ['POST', '/notas-credito', 'Emitir nota de crédito'],
                            ['POST', '/notas-debito', 'Emitir nota de débito'],
                        ],
                        'Guías de remisión' => [
                            ['POST', '/guias-remision', 'Emitir guía de remisión'],
                            ['POST', '/guias-remision/{id}/check-status', 'Consultar estado (flujo asíncrono)'],
                        ],
                        'Otros' => [
                            ['POST', '/resumenes', 'Resumen diario de boletas'],
                            ['POST', '/anulaciones', 'Comunicación de baja / anulación'],
                            ['POST', '/consulta-cpe/boleta/{id}', 'Consultar estado en SUNAT'],
                        ],
                    ];
                @endphp

                @foreach ($grupos as $grupo => $rutas)
                    <div>
                        <h3 class="mb-2 text-sm font-semibold uppercase text-gray-500">{{ $grupo }}</h3>
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full text-sm">
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($rutas as [$metodo, $ruta, $desc])
                                        <tr>
                                            <td class="px-3 py-2 w-16">
                                                <span class="rounded px-2 py-0.5 text-xs font-semibold {{ $metodo === 'GET' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">{{ $metodo }}</span>
                                            </td>
                                            <td class="px-3 py-2 font-mono text-xs text-gray-800 whitespace-nowrap">{{ $ruta }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $desc }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </section>

            {{-- 4. EJEMPLOS --}}
            <section id="ejemplos" class="scroll-mt-24 space-y-4">
                <h2 class="text-xl font-semibold text-gray-900">4. Ejemplos de código</h2>

                <div>
                    <h3 class="mb-2 text-sm font-semibold uppercase text-gray-500">Probar la conexión (curl)</h3>
                    <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-xs text-gray-100"><code>curl {{ url('/api') }}/empresa \
  -H "X-Api-Key: TU_API_KEY" \
  -H "X-Api-Secret: TU_API_SECRET"</code></pre>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-semibold uppercase text-gray-500">Emitir una boleta (curl)</h3>
                    <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-xs text-gray-100"><code>curl -X POST {{ url('/api') }}/boletas \
  -H "X-Api-Key: TU_API_KEY" \
  -H "X-Api-Secret: TU_API_SECRET" \
  -H "Content-Type: application/json" \
  -d '{
    "serie": "B001",
    "cliente": {
      "tipo_documento": "1",
      "numero_documento": "70057895",
      "razon_social": "JUAN PEREZ"
    },
    "items": [
      { "descripcion": "LAPTOP", "cantidad": 1, "valor_unitario": 1200.00 }
    ]
  }'</code></pre>
                    <p class="mt-2 text-xs text-gray-500">El formato exacto de los campos te lo confirma tu contacto en Cisma Fact según el tipo de comprobante. La respuesta incluye el estado SUNAT y los enlaces a PDF / XML / CDR.</p>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-semibold uppercase text-gray-500">PHP</h3>
                    <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-xs text-gray-100"><code>$ch = curl_init('{{ url('/api') }}/empresa');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'X-Api-Key: TU_API_KEY',
        'X-Api-Secret: TU_API_SECRET',
    ],
]);
$respuesta = curl_exec($ch);
echo $respuesta;</code></pre>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-semibold uppercase text-gray-500">Python</h3>
                    <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-xs text-gray-100"><code>import requests

resp = requests.get(
    "{{ url('/api') }}/empresa",
    headers={
        "X-Api-Key": "TU_API_KEY",
        "X-Api-Secret": "TU_API_SECRET",
    },
)
print(resp.json())</code></pre>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-semibold uppercase text-gray-500">JavaScript / Node.js</h3>
                    <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-xs text-gray-100"><code>const resp = await fetch("{{ url('/api') }}/empresa", {
  headers: {
    "X-Api-Key": "TU_API_KEY",
    "X-Api-Secret": "TU_API_SECRET",
  },
});
const data = await resp.json();
console.log(data);</code></pre>
                </div>
            </section>

            <div class="rounded-lg border border-blue-200 bg-blue-50 p-5 text-sm text-blue-800">
                ¿Listo para integrar? <a href="{{ route('register') }}" class="font-semibold underline">Crea tu cuenta</a> o solicita un <strong>token de pruebas (sandbox)</strong> para empezar sin compromiso.
            </div>
        </main>
    </div>

    <footer class="border-t border-gray-100 py-6 text-center text-sm text-gray-400">
        © {{ date('Y') }} Cisma Fact — Facturación electrónica SUNAT
    </footer>
</body>
</html>
