@extends('layouts.app')

@section('title', 'Rendimiento API')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('super-admin.api-global') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Rendimiento API</h1>
                <p class="text-gray-500 mt-1">Métricas de rendimiento de los últimos 30 días</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <x-stat-card title="Uptime" :value="$uptime . '%'" color="green">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Tiempo Promedio" :value="number_format($avgResponseTime, 0) . 'ms'" subtitle="últimos 30 días" color="blue">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Total Requests" :value="number_format($totalRequests)" subtitle="últimos 30 días" color="purple">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Errores (30d)" :value="number_format($totalRequests - $statusDistribution->where('status_code', '<', 400)->sum('total'))" color="red">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Rendimiento Diario (últimos 7 días)</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Fecha</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Requests</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Promedio (ms)</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Mínimo (ms)</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Máximo (ms)</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Gráfico</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dailyPerformance as $day)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $day['fecha'] }}</td>
                        <td class="py-3 px-4 text-gray-500">{{ number_format($day['total']) }}</td>
                        <td class="py-3 px-4 text-gray-500">{{ number_format($day['avg_time'], 0) }}</td>
                        <td class="py-3 px-4 text-green-600">{{ number_format($day['min_time'], 0) }}</td>
                        <td class="py-3 px-4 text-red-600">{{ number_format($day['max_time'], 0) }}</td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                                    <div class="bg-blue-500 h-full rounded-full" style="width: {{ min($day['avg_time'] / 10, 100) }}%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Endpoints Más Usados (30 días)</h2>
            @if($topEndpoints->count() > 0)
                <div class="space-y-3">
                    @foreach($topEndpoints as $ep)
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $ep->path }}</p>
                            <p class="text-xs text-gray-500">Promedio: {{ number_format($ep->avg_time, 0) }}ms | Máximo: {{ number_format($ep->max_time, 0) }}ms</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-24 bg-gray-100 rounded-full h-3 overflow-hidden">
                                <div class="bg-purple-500 h-full rounded-full" style="width: {{ min($ep->total * 2, 100) }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-700 w-16 text-right">{{ number_format($ep->total) }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm text-center py-4">No hay datos de uso.</p>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Distribución por Status Code (30 días)</h2>
            @if($statusDistribution->count() > 0)
                <div class="space-y-3">
                    @foreach($statusDistribution as $status)
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-1 {{ $status->status_code < 400 ? 'bg-green-100 text-green-700' : ($status->status_code < 500 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }} rounded text-xs font-medium w-16 text-center">
                            {{ $status->status_code }}
                        </span>
                        <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                            <div class="{{ $status->status_code < 400 ? 'bg-green-500' : ($status->status_code < 500 ? 'bg-yellow-500' : 'bg-red-500') }} h-full rounded-full" style="width: {{ min(($status->total / $totalRequests) * 100, 100) }}%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700 w-20 text-right">{{ number_format($status->total) }} ({{ round(($status->total / $totalRequests) * 100, 1) }}%)</span>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm text-center py-4">No hay datos de distribución.</p>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Errores por Empresa (30 días)</h2>
        @if($erroresPorEmpresa->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50">
                            <th class="text-left py-3 px-4 font-medium text-gray-500">Empresa</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-500">Errores</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-500">Gráfico</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($erroresPorEmpresa as $item)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-4 font-medium">{{ $item->company->razon_social ?? 'N/A' }}</td>
                            <td class="py-3 px-4 text-red-600 font-medium">{{ number_format($item->total) }}</td>
                            <td class="py-3 px-4">
                                <div class="w-48 bg-gray-100 rounded-full h-3 overflow-hidden">
                                    <div class="bg-red-500 h-full rounded-full" style="width: {{ min($item->total * 5, 100) }}%"></div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 text-sm text-center py-4">No hay errores registrados.</p>
        @endif
    </div>
</div>
@endsection
