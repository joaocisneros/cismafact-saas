@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
<div class="space-y-6">
    <h2 class="text-lg font-semibold text-gray-800">Reportes</h2>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-chart-bar title="Ventas Diarias (Últimos 7 días)" :data="$ventasDiarias" />
        <x-chart-bar title="Ventas Mensuales (Últimos 6 meses)" :data="$ventasMensuales" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-chart-bar title="Documentos por Tipo" :data="$docsPorTipo" />
        <x-chart-bar title="Consumo API (Últimos 7 días)" :data="$consumoApi" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-4">Resumen de Ventas</h3>
            <div class="space-y-3">
                @php
                    $totalVentas = collect($ventasDiarias)->sum('value');
                    $totalMes = collect($ventasMensuales)->last()['value'] ?? 0;
                @endphp
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Total Últimos 7 días</span>
                    <span class="text-sm font-semibold">S/ {{ number_format($totalVentas, 2) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Último Mes</span>
                    <span class="text-sm font-semibold">S/ {{ number_format($totalMes, 2) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                    <span class="text-sm text-blue-600 font-medium">IGV Generado (18%)</span>
                    <span class="text-sm font-semibold text-blue-700">S/ {{ number_format($igvGenerado, 2) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                    <span class="text-sm text-green-600 font-medium">Ticket Promedio</span>
                    <span class="text-sm font-semibold text-green-700">S/ {{ number_format($ticketPromedio, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-4">Resumen de Documentos</h3>
            <div class="space-y-3">
                @foreach($docsPorTipo as $doc)
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm text-gray-600">{{ $doc['label'] }}</span>
                        <span class="text-sm font-semibold">{{ $doc['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-md font-semibold text-gray-800">Exportar Datos</h3>
            <div class="flex gap-2">
                <a href="{{ route('empresa.reports.export', ['type' => 'general']) }}" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">Exportar CSV</a>
            </div>
        </div>
    </div>
</div>
@endsection
