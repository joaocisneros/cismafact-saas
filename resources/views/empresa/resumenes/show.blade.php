@extends('layouts.app')

@section('title', 'Resumen diario')

@section('content')
@php
    $cls = match($resumen->estado_sunat) {
        'ACEPTADO' => 'bg-green-50 text-green-700 border-green-200',
        'RECHAZADO','ERROR' => 'bg-red-50 text-red-700 border-red-200',
        'PROCESANDO','ENVIADO' => 'bg-blue-50 text-blue-700 border-blue-200',
        default => 'bg-amber-50 text-amber-700 border-amber-200',
    };
    $resp = is_string($resumen->respuesta_sunat) ? json_decode($resumen->respuesta_sunat, true) : ($resumen->respuesta_sunat ?? []);
    $msg = $resp['description'] ?? $resp['message'] ?? null;
@endphp
<div class="space-y-5 max-w-3xl">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('empresa.resumenes.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver a resúmenes</a>
            <h1 class="text-2xl font-bold text-gray-800 mt-1">Resumen diario de boletas</h1>
            <p class="text-gray-500">RC-{{ \Illuminate\Support\Carbon::parse($resumen->fecha_generacion)->format('Ymd') }}-{{ $resumen->correlativo }}</p>
        </div>
        <span class="rounded-full border px-3 py-1 text-sm font-medium {{ $cls }}">{{ $resumen->estado_sunat }}</span>
    </div>

    @if(session('success'))<div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>@endif

    <div class="flex flex-wrap gap-2">
        @if($resumen->ticket && $resumen->estado_sunat !== 'ACEPTADO')
            <form method="POST" action="{{ route('empresa.resumenes.check-status', $resumen->id) }}">
                @csrf
                <button class="rounded-md bg-emerald-600 text-white px-4 py-2 text-sm font-medium hover:bg-emerald-700">Consultar estado SUNAT</button>
            </form>
        @endif
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-5">
        <dl class="grid grid-cols-2 gap-y-2 text-sm">
            <dt class="text-gray-500">Fecha de las boletas</dt><dd class="text-gray-800">{{ \Illuminate\Support\Carbon::parse($resumen->fecha_resumen)->format('d/m/Y') }}</dd>
            <dt class="text-gray-500">Fecha de generación</dt><dd class="text-gray-800">{{ \Illuminate\Support\Carbon::parse($resumen->fecha_generacion)->format('d/m/Y') }}</dd>
            @if($resumen->ticket)<dt class="text-gray-500">Ticket</dt><dd class="font-mono text-gray-800">{{ $resumen->ticket }}</dd>@endif
            @if($msg)<dt class="text-gray-500">Respuesta SUNAT</dt><dd class="text-gray-800">{{ $msg }}</dd>@endif
        </dl>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr><th class="px-4 py-3">Boleta</th><th class="px-4 py-3">Acción</th><th class="px-4 py-3 text-right">Total</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach(($resumen->detalles ?? []) as $d)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $d['serie_numero'] ?? '' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ ($d['estado'] ?? '') === '3' ? 'Anulación' : 'Adición' }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">S/ {{ number_format($d['total'] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
