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

        </nav>

    </aside>

    {{-- El avatar y los modales comparten estado: se abren desde el menu
         de la cuenta, esten en la pantalla que esten. --}}
    <div x-data="{ modal: @js(session('abrir_modal')) }" @keydown.escape.window="modal = null" class="flex min-w-0 flex-1 flex-col">

        {{-- Barra superior: el avatar va aquí y no al fondo del menú, porque es
             donde está en el resto del sistema y donde se busca. --}}
        <header class="flex items-center justify-end gap-3 border-b border-gray-200 bg-white px-4 py-2.5 lg:px-6">
            @php
                $iniciales = collect(explode(' ', trim((string) auth()->user()->name)))
                    ->filter()
                    ->take(2)
                    ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
                    ->implode('') ?: 'U';
            @endphp

            <div x-data="{ cuenta: false }" @click.outside="cuenta = false" class="relative">
                <button type="button" @click="cuenta = ! cuenta"
                        class="flex items-center gap-2 rounded-md py-1.5 pl-1.5 pr-2 text-sm hover:bg-gray-100">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">
                        {{ $iniciales }}
                    </span>
                    <span class="hidden max-w-[160px] text-left sm:block">
                        <span class="block truncate text-sm font-medium text-gray-800">{{ auth()->user()->name }}</span>
                        <span class="block text-xs text-gray-500">Cliente de RUC y DNI</span>
                    </span>
                    <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="cuenta && 'rotate-180'"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="cuenta" x-cloak x-transition.opacity
                     class="absolute right-0 z-50 mt-2 w-60 rounded-lg border border-gray-200 bg-white py-1.5 shadow-lg">
                    <div class="border-b border-gray-100 px-4 py-2.5">
                        <p class="truncate text-sm font-medium text-gray-800">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
                    </div>

                    <button type="button" @click="cuenta = false; modal = 'datos'"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50">
                        <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z"/>
                        </svg>
                        Editar mis datos
                    </button>

                    <button type="button" @click="cuenta = false; modal = 'clave'"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50">
                        <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z"/>
                        </svg>
                        Cambiar contraseña
                    </button>

                    <a href="{{ route('consultas.credenciales') }}"
                       class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4-2a6 6 0 01-7.7 5.7L11 15H9v2H7v2H4a1 1 0 01-1-1v-2.6a1 1 0 01.3-.7l6-6A6 6 0 1121 7z"/>
                        </svg>
                        Mi clave y mi secreto
                    </a>

                    <div class="my-1 border-t border-gray-100"></div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50">
                            <svg class="h-4 w-4 text-red-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 006 5.25v13.5a1.5 1.5 0 001.5 1.5h6a1.5 1.5 0 001.5-1.5V15a.75.75 0 011.5 0v3.75a3 3 0 01-3 3h-6a3 3 0 01-3-3V5.25a3 3 0 013-3h6a3 3 0 013 3V9A.75.75 0 0115 9V5.25a1.5 1.5 0 00-1.5-1.5h-6zm10.72 4.72a.75.75 0 011.06 0l3 3a.75.75 0 010 1.06l-3 3a.75.75 0 11-1.06-1.06l1.72-1.72H9a.75.75 0 010-1.5h10.94l-1.72-1.72a.75.75 0 010-1.06z"/>
                            </svg>
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Menú de móvil: el lateral no cabe --}}
        <div class="flex items-center gap-2 overflow-x-auto border-b border-gray-200 bg-white px-3 py-2 md:hidden">
            @foreach([
                'consultas.panel' => 'Panel',
                'consultas.credenciales' => 'Mi API',
                'consultas.consumo' => 'Consumo',
                'consultas.consultas' => 'Consultas',
                'consultas.documentacion' => 'Docs',
            ] as $ruta => $texto)
                <a href="{{ route($ruta) }}"
                   class="whitespace-nowrap rounded-md px-2.5 py-1.5 text-sm font-medium {{ request()->routeIs($ruta) ? 'bg-blue-50 text-blue-700' : 'text-gray-600' }}">
                    {{ $texto }}
                </a>
            @endforeach
        </div>

        <main class="flex-1 p-4 lg:p-6">
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

        {{-- Los datos de la cuenta y la contraseña, detrás del avatar.
             Viven en el armazón y no en una pantalla suya porque no son un
             módulo del servicio: se abren desde donde estés. --}}
        <div x-show="modal === 'datos'" x-cloak
             class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
            <div @click.outside="modal = null" class="my-auto w-full max-w-md rounded-xl bg-white shadow-xl">
                <form method="POST" action="{{ route('consultas.cuenta.guardar') }}">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h3 class="font-semibold text-gray-900">Editar mis datos</h3>
                        <button type="button" @click="modal = null" class="text-2xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <div class="space-y-4 p-5">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-gray-600">Nombre</span>
                            <input name="name" value="{{ old('name', auth()->user()->name) }}" required maxlength="120"
                                   class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-[15px]">
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-gray-600">Correo &mdash; con este entras</span>
                            <input name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required maxlength="150"
                                   class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-[15px]">
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
                        <button type="button" @click="modal = null"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Cancelar</button>
                        <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="modal === 'clave'" x-cloak
             class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
            <div @click.outside="modal = null" class="my-auto w-full max-w-md rounded-xl bg-white shadow-xl">
                <form method="POST" action="{{ route('consultas.cuenta.clave') }}">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h3 class="font-semibold text-gray-900">Cambiar contrase&ntilde;a</h3>
                        <button type="button" @click="modal = null" class="text-2xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <div class="space-y-4 p-5">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-gray-600">Contrase&ntilde;a actual</span>
                            <input name="actual" type="password" required autocomplete="current-password"
                                   class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-[15px]">
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-gray-600">Contrase&ntilde;a nueva</span>
                            <input name="nueva" type="password" required minlength="8" autocomplete="new-password"
                                   placeholder="Al menos 8 caracteres"
                                   class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-[15px]">
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-sm font-semibold text-gray-600">Repite la nueva</span>
                            <input name="nueva_confirmation" type="password" required minlength="8" autocomplete="new-password"
                                   class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-[15px]">
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
                        <button type="button" @click="modal = null"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Cancelar</button>
                        <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Cambiar contrase&ntilde;a</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
