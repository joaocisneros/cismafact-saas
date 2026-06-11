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
            <button type="button"
                    onclick="window.openAdminModal('{{ route('super-admin.api-global.api-keys') }}', 'API Keys')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                API Keys
            </button>
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
</div>
@endsection
