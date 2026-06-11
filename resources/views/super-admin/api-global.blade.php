@extends('layouts.app')

@section('title', 'API')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Administración de API</h2>
            <p class="mt-1 text-sm text-gray-500">Resumen ligero del servicio y accesos bajo demanda.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('super-admin.api-global.api-keys') }}"
               class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                API Keys
            </a>
            <button type="button"
                    onclick="window.openAdminModal('{{ route('super-admin.api-global.performance') }}', 'Rendimiento de API')"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Rendimiento
            </button>
            <button type="button"
                    onclick="window.openAdminModal('{{ route('super-admin.api-global.logs') }}', 'Últimos logs de API')"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Ver logs
            </button>
        </div>
    </div>

    <div class="overflow-hidden border-y border-gray-200 bg-white">
        <dl class="grid grid-cols-2 divide-x divide-y divide-gray-200 md:grid-cols-4 md:divide-y-0">
            <div class="p-4">
                <dt class="text-xs font-medium uppercase text-gray-500">Solicitudes hoy</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($consumoHoy) }}</dd>
            </div>
            <div class="p-4">
                <dt class="text-xs font-medium uppercase text-gray-500">Solicitudes del mes</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($consumoMes) }}</dd>
            </div>
            <div class="p-4">
                <dt class="text-xs font-medium uppercase text-gray-500">Errores hoy</dt>
                <dd class="mt-1 text-2xl font-semibold {{ $erroresHoy > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ number_format($erroresHoy) }}</dd>
            </div>
            <div class="p-4">
                <dt class="text-xs font-medium uppercase text-gray-500">Tiempo promedio</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($tiempoPromedio, 0) }} ms</dd>
                <p class="mt-1 text-xs text-gray-500">{{ number_format($apiKeyActivas) }} API keys activas</p>
            </div>
        </dl>
    </div>

    <div class="border-y border-gray-200 bg-white px-5 py-6">
        <h3 class="text-sm font-semibold text-gray-900">Estado del servicio</h3>
        <div class="mt-3 flex items-center gap-2 text-sm text-green-700">
            <span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
            API disponible para recibir solicitudes
        </div>
    </div>

    {{-- ===== Token Sandbox (pruebas para programadores) ===== --}}
    <div class="border-y border-gray-200 bg-white px-5 py-6">
        <h3 class="text-sm font-semibold text-gray-900">Token Sandbox (pruebas)</h3>
        <p class="mt-1 text-sm text-gray-500">
            Genera un token de prueba para entregárselo a un programador. Emite solo a <strong>SUNAT beta</strong> (documentos sin valor legal), sin tope, ligado a tu empresa demo. Cada token lleva el nombre del dev para ver su consumo y bloquearlo individualmente.
        </p>

        @if (session('sandbox_token'))
            @php($st = session('sandbox_token'))
            <div class="mt-4 rounded-md border border-green-300 bg-green-50 p-4">
                <p class="text-sm font-semibold text-green-800">✅ {{ $st['name'] }} — cópialo ahora (el secreto no se vuelve a mostrar):</p>
                <dl class="mt-3 space-y-2 text-sm">
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">X-Api-Key</dt>
                        <dd class="mt-1 break-all rounded bg-white px-3 py-2 font-mono text-gray-800 ring-1 ring-gray-200">{{ $st['key'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">X-Api-Secret</dt>
                        <dd class="mt-1 break-all rounded bg-white px-3 py-2 font-mono text-gray-800 ring-1 ring-gray-200">{{ $st['secret'] }}</dd>
                    </div>
                </dl>
            </div>
        @endif

        @if (session('error'))
            <div class="mt-4 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('super-admin.api-global.sandbox-token') }}" class="mt-4 flex flex-wrap items-end gap-3">
            @csrf
            <label class="text-sm font-medium text-gray-700">
                Nombre del dev / proyecto
                <input type="text" name="dev_name" required placeholder="Ej: Juan Pérez (ERP X)"
                       class="mt-1 block w-64 rounded-md border border-gray-300 px-3 py-2 text-sm">
            </label>
            <label class="text-sm font-medium text-gray-700">
                Caduca en (días, opcional)
                <input type="number" name="expires_in_days" min="1" max="365" placeholder="Ej: 30"
                       class="mt-1 block w-44 rounded-md border border-gray-300 px-3 py-2 text-sm">
            </label>
            <button type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Generar token sandbox
            </button>
        </form>
    </div>
</div>
@endsection
