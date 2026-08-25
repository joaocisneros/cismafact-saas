<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - @yield('title', 'Acceso')</title>
    <link rel="icon" href="{{ config('platform.favicon_url', asset('assets/brand/favicon.png')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<style>
    /* La barra de desplazamiento no se pinta en las pantallas de acceso: el
       formulario ya cabe, y cuando falta poco esa barra gris al costado se ve
       como un fallo. El desplazamiento sigue funcionando con la rueda y el
       gesto del trackpad, asi que en pantallas bajas nada queda inalcanzable. */
    html, body { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar, body::-webkit-scrollbar { width: 0; height: 0; display: none; }
</style>

<body class="min-h-screen flex bg-gray-50">

    {{-- Panel de marca (izquierda, oculto en móvil) --}}
    <aside class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-blue-700 via-blue-800 to-indigo-900 text-white p-12 flex-col justify-between">
        {{-- Adornos --}}
        <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-32 -left-16 w-96 h-96 rounded-full bg-white/5"></div>

        <div class="relative z-10">
            <a href="/" class="inline-flex items-center gap-2">
                <span class="text-2xl font-bold tracking-tight">Cisma Fact</span>
            </a>
        </div>

        <div class="relative z-10">
            <h1 class="text-4xl font-bold leading-tight">Facturación electrónica<br>directo a SUNAT</h1>
            <p class="mt-4 text-blue-100 max-w-md">Emite facturas, boletas, notas y guías de remisión con tu propio certificado. Sin intermediarios.</p>

            <ul class="mt-8 space-y-3 text-blue-50">
                @foreach([
                    'Todos tus comprobantes en un solo lugar',
                    'Firmas con tu propio certificado digital',
                    'API REST para integrar tu negocio',
                    'Consulta el estado real en SUNAT',
                ] as $item)
                    <li class="flex items-center gap-3">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-sm">✓</span>
                        {{ $item }}
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="relative z-10 text-sm text-blue-200">© {{ date('Y') }} {{ config('app.name') }}. Hecho en Perú 🇵🇪</p>
    </aside>

    {{-- Formulario (derecha) --}}
    <main class="flex w-full items-center justify-center p-6 sm:p-8 lg:w-1/2">
        <div class="w-full max-w-lg">
            {{-- Logo --}}
            <div class="mb-5 text-center">
                <img src="{{ config('platform.logo_url', asset('assets/brand/cisma-fact.png')) }}"
                     alt="{{ config('app.name') }}"
                     class="mx-auto h-auto w-40 max-w-full">
                <p class="mt-2 text-sm text-gray-500">Facturación electrónica compatible con SUNAT</p>
            </div>

            {{-- Tarjeta --}}
            <div class="rounded-2xl bg-white p-7 shadow-xl ring-1 ring-gray-100">
                @if(session('success'))
                    <div class="flex items-start gap-2 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-5 text-sm">
                        <span>✅</span><span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-5">
                        <ul class="list-disc list-inside text-sm space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>

            <p class="mt-4 text-center text-xs text-gray-400">
                © {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
            </p>
        </div>
    </main>

</body>
</html>
