@extends('layouts.app')

@section('title', 'Reporte Semanal')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Reporte Semanal</h1>
            <p class="text-gray-500 mt-1">Ventas de las últimas 4 semanas</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('empresa.reports.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Volver</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Ventas por Semana</h2>
        <div class="space-y-3">
            @foreach($data as $week)
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500 w-32">{{ $week['label'] }}</span>
                <div class="flex-1 bg-gray-100 rounded-full h-6 overflow-hidden">
                    <div class="bg-blue-500 h-full rounded-full flex items-center justify-end pr-2"
                         style="width: {{ max(($week['value'] / max($data->pluck('value')->max(), 1)) * 100, 5) }}%">
                        <span class="text-xs text-white font-medium">S/ {{ number_format($week['value'], 2) }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Resumen</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-blue-600">S/ {{ number_format($data->sum('value'), 2) }}</p>
                <p class="text-sm text-gray-500">Total 4 semanas</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-green-600">S/ {{ number_format($data->avg('value'), 2) }}</p>
                <p class="text-sm text-gray-500">Promedio semanal</p>
            </div>
        </div>
    </div>
</div>
@endsection
