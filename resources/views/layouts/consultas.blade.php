{{--
    El armazón del panel del cliente de RUC y DNI.

    Aparte del de empresa a propósito: quien solo compra consultas no tiene
    comprobantes, ni sucursales, ni certificado, así que ese menú le enseñaría
    seis cosas que no puede usar. Aquí solo está lo suyo.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - @yield('title', 'Mi acceso a la API')</title>
    <link rel="icon" href="{{ config('platform.favicon_url', asset('assets/brand/favicon.png')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-100 text-gray-900 antialiased">

<div class="flex min-h-screen">

    {{-- Menú --}}
    <aside class="hidden w-56 shrink-0 flex-col border-r border-gray-200 bg-white md:flex">
        <div class="flex items-center gap-2.5 border-b border-gray-100 px-4 py-4">
            <div class="grid h-9 w-9 place-items-center rounded-lg bg-blue-600 text-xs font-bold text-white">CF</div>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-gray-900">{{ config('app.name') }}</p>
                <p class="text-xs text-gray-500">API de RUC y DNI</p>
            </div>
        </div>

        <nav class="flex-1 space-y-0.5 px-2 py-3">
            <p class="px-2 pb-1 pt-2 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Mi servicio</p>

            <x-consultas-enlace ruta="consultas.panel" texto="Panel">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v8H4zM14 4h6v5h-6zM14 13h6v7h-6zM4 16h6v4H4z"/>
            </x-consultas-enlace>

            <x-consultas-enlace ruta="consultas.credenciales" texto="Mi API">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4-2a6 6 0 01-7.7 5.7L11 15H9v2H7v2H4a1 1 0 01-1-1v-2.6a1 1 0 01.3-.7l6-6A6 6 0 1121 7z"/>
            </x-consultas-enlace>

            <x-consultas-enlace ruta="consultas.consumo" texto="Consumo">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 20V10m6 10V4m6 16v-7m6 7H2"/>
            </x-consultas-enlace>

            <x-consultas-enlace ruta="consultas.consultas" texto="Mis consultas">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.2-5.2M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
            </x-consultas-enlace>

            <p class="px-2 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Ayuda</p>

            <x-consultas-enlace ruta="consultas.documentacion" texto="Documentación">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h4M5 4h9l5 5v11a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1z"/>
            </x-consultas-enlace>

            <x-consultas-enlace ruta="consultas.cuenta" texto="Mi cuenta">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8a4 4 0 11-8 0 4 4 0 018 0zM4 21a8 8 0 0116 0"/>
            </x-consultas-enlace>
        </nav>

        <div class="border-t border-gray-100 px-4 py-3">
            <p class="truncate text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button class="text-xs font-medium text-gray-500 hover:text-gray-800">Cerrar sesión</button>
            </form>
        </div>
    </aside>

    <div class="min-w-0 flex-1">
        {{-- Menú de móvil: el lateral no cabe --}}
        <div class="flex items-center gap-2 overflow-x-auto border-b border-gray-200 bg-white px-3 py-2 md:hidden">
            @foreach([
                'consultas.panel' => 'Panel',
                'consultas.credenciales' => 'Mi API',
                'consultas.consumo' => 'Consumo',
                'consultas.consultas' => 'Consultas',
                'consultas.documentacion' => 'Docs',
                'consultas.cuenta' => 'Cuenta',
            ] as $ruta => $texto)
                <a href="{{ route($ruta) }}"
                   class="whitespace-nowrap rounded-md px-2.5 py-1.5 text-sm font-medium {{ request()->routeIs($ruta) ? 'bg-blue-50 text-blue-700' : 'text-gray-600' }}">
                    {{ $texto }}
                </a>
            @endforeach
        </div>

        <main class="p-4 lg:p-6">
            @if(session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

</body>
</html>
