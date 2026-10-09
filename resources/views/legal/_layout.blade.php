<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Cisma Fact</title>
    <meta name="robots" content="index, follow">
    <link rel="icon" href="{{ config('platform.favicon_url', asset('assets/brand/favicon.png')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
            <a href="{{ route('landing') }}" class="flex items-center" aria-label="Ir al inicio de Cisma Fact">
                <img src="{{ config('platform.logo_url', asset('assets/brand/cisma-fact.png')) }}"
                     alt="{{ config('app.name', 'Cisma Fact') }}"
                     class="h-10 w-auto max-w-[180px] object-contain">
            </a>
            <a href="{{ route('landing') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Volver al inicio</a>
        </div>
    </header>
    <main class="mx-auto max-w-5xl px-6 py-12 sm:py-16">
        <div class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm sm:p-10">
            <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Información legal</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-950">{{ $title }}</h1>
            <p class="mt-2 text-sm text-slate-500">Última actualización: 8 de octubre de 2026</p>
            <div class="mt-8 space-y-7 text-sm leading-7 text-slate-600">
                {{ $slot }}
            </div>
        </div>
    </main>
    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">
        © {{ date('Y') }} Cisma Fact Perú · <a href="mailto:sistemasdesk04@gmail.com" class="text-blue-600">sistemasdesk04@gmail.com</a>
    </footer>
</body>
</html>
