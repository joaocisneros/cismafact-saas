@extends('layouts.app')

@section('title', 'Reporte Anual')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Reporte Anual</h1>
            <p class="text-gray-500 mt-1">Ventas de los últimos 12 meses</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('empresa.reports.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Volver</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Ventas Mensuales</h2>
        <div class="space-y-3">
            @foreach($data as $month)
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500 w-24">{{ $month['label'] }}</span>
                <div class="flex-1 bg-gray-100 rounded-full h-6 overflow-hidden">
                    <div class="bg-blue-500 h-full rounded-full flex items-center justify-end pr-2"
                         style="width: {{ max(($month['value'] / max($data->pluck('value')->max(), 1)) * 100, 5) }}%">
                        <span class="text-xs text-white font-medium">S/ {{ number_format($month['value'], 2) }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Resumen Anual</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-blue-600">S/ {{ number_format($data->sum('value'), 2) }}</p>
                <p class="text-sm text-gray-500">Total anual</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-green-600">S/ {{ number_format($data->avg('value'), 2) }}</p>
                <p class="text-sm text-gray-500">Promedio mensual</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-purple-600">S/ {{ number_format($data->max('value'), 2) }}</p>
                <p class="text-sm text-gray-500">Mejor mes</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-red-600">S/ {{ number_format($data->min('value'), 2) }}</p>
                <p class="text-sm text-gray-500">Peor mes</p>
            </div>
        </div>
    </div>
</div>
@endsection
