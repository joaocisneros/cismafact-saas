@extends('layouts.app')

@section('title', 'Estadísticas')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Estadísticas</h1>
            <p class="text-gray-500 mt-1">Métricas globales de la plataforma</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('super-admin.export.statistics', 'csv') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">Exportar CSV</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-stat-card title="Empresas Activas" :value="$empresasActivas" subtitle="{{ $empresasInactivas }} inactivas" color="blue">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Facturas Emitidas" :value="$totalFacturas" subtitle="{{ $facturasMes }} este mes" color="green">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Boletas Emitidas" :value="$totalBoletas" subtitle="{{ $boletasMes }} este mes" color="purple">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Consumo API Año" :value="number_format($consumoApiAnio)" subtitle="{{ number_format($consumoApiHoy) }} hoy" color="orange">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Ventas</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Este Mes</span>
                    <span class="text-lg font-bold text-green-600">S/ {{ number_format($ventasMes, 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Este Año</span>
                    <span class="text-lg font-bold text-green-600">S/ {{ number_format($ventasAnio, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Documentos por Tipo</h2>
            <div class="space-y-3">
                @foreach($docsPorTipo as $tipo => $count)
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">{{ $tipo }}</span>
                    <span class="font-bold text-gray-800">{{ number_format($count) }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Crecimiento Empresas</h2>
            <div class="space-y-3">
                @foreach($crecimientoEmpresas->take(3) as $item)
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">{{ $item['mes'] }}</span>
                    <span class="font-bold text-gray-800">{{ $item['total'] }} <span class="text-xs text-gray-500">({{ $item['activas'] }} activas)</span></span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Actividad Diaria (últimos 7 días)</h2>
            <div class="space-y-2">
                @foreach($actividadDiaria as $dia)
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500 w-12">{{ $dia['fecha'] }}</span>
                    <div class="flex-1 bg-gray-100 rounded-full h-4 overflow-hidden">
                        <div class="bg-blue-500 h-full rounded-full" style="width: {{ min(($dia['facturas'] + $dia['boletas']) * 10, 100) }}%"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-700 w-16 text-right">{{ $dia['facturas'] + $dia['boletas'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Consumo API Diario</h2>
            <div class="space-y-2">
                @foreach($consumoApiDiario as $dia)
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500 w-12">{{ $dia['fecha'] }}</span>
                    <div class="flex-1 bg-gray-100 rounded-full h-4 overflow-hidden">
                        <div class="bg-orange-500 h-full rounded-full" style="width: {{ min($dia['requests'] * 2, 100) }}%"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-700 w-16 text-right">{{ number_format($dia['requests']) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Actividad Mensual (últimos 6 meses)</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500">
                        <th class="pb-3 font-medium">Mes</th>
                        <th class="pb-3 font-medium text-center">Facturas</th>
                        <th class="pb-3 font-medium text-center">Boletas</th>
                        <th class="pb-3 font-medium text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($actividadMensual as $mes)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 font-medium">{{ $mes['mes'] }}</td>
                        <td class="py-3 text-center">{{ $mes['facturas'] }}</td>
                        <td class="py-3 text-center">{{ $mes['boletas'] }}</td>
                        <td class="py-3 text-center font-bold">{{ $mes['facturas'] + $mes['boletas'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Actividad Anual (últimos 12 meses)</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500">
                        <th class="pb-3 font-medium">Mes</th>
                        <th class="pb-3 font-medium text-center">Facturas</th>
                        <th class="pb-3 font-medium text-center">Boletas</th>
                        <th class="pb-3 font-medium text-center">Ventas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($actividadAnual as $mes)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 font-medium">{{ $mes['mes'] }}</td>
                        <td class="py-3 text-center">{{ $mes['facturas'] }}</td>
                        <td class="py-3 text-center">{{ $mes['boletas'] }}</td>
                        <td class="py-3 text-center font-bold">S/ {{ number_format($mes['ventas'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
