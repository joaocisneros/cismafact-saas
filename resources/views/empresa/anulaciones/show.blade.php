@extends('layouts.app')

@section('title', 'Anulación')

@section('content')
@php
    $cls = match($anulacion->estado_sunat) {
        'ACEPTADO' => 'bg-green-50 text-green-700 border-green-200',
        'RECHAZADO','ERROR' => 'bg-red-50 text-red-700 border-red-200',
        'ENVIADO','PROCESANDO' => 'bg-blue-50 text-blue-700 border-blue-200',
        default => 'bg-amber-50 text-amber-700 border-amber-200',
    };
    $resp = is_string($anulacion->respuesta_sunat) ? json_decode($anulacion->respuesta_sunat, true) : ($anulacion->respuesta_sunat ?? []);
    $msg = $resp['description'] ?? $resp['message'] ?? null;
@endphp
<div class="space-y-5 max-w-3xl">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('empresa.anulaciones.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver a anulaciones</a>
            <h1 class="text-2xl font-bold text-gray-800 mt-1">Comunicación de baja</h1>
            <p class="text-gray-500">RA-{{ \Illuminate\Support\Carbon::parse($anulacion->fecha_generacion)->format('Ymd') }}-{{ $anulacion->correlativo }}</p>
        </div>
        <span class="rounded-full border px-3 py-1 text-sm font-medium {{ $cls }}">{{ $anulacion->estado_sunat }}</span>
    </div>

    @if(session('success'))<div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>@endif

    <div class="flex flex-wrap gap-2">
        @if($anulacion->ticket && $anulacion->estado_sunat !== 'ACEPTADO')
            <form method="POST" action="{{ route('empresa.anulaciones.check-status', $anulacion->id) }}">
                @csrf
                <button class="rounded-md bg-emerald-600 text-white px-4 py-2 text-sm font-medium hover:bg-emerald-700">Consultar estado SUNAT</button>
            </form>
        @endif
        @if($anulacion->cdr_path)
            <a href="{{ route('empresa.documents.download', ['anulacion', $anulacion->id, 'cdr']) }}" class="rounded-md bg-gray-100 text-gray-700 px-4 py-2 text-sm font-medium hover:bg-gray-200">CDR</a>
        @endif
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-5">
        <dl class="grid grid-cols-2 gap-y-2 text-sm">
            <dt class="text-gray-500">Fecha de los comprobantes</dt><dd class="text-gray-800">{{ \Illuminate\Support\Carbon::parse($anulacion->fecha_referencia)->format('d/m/Y') }}</dd>
            <dt class="text-gray-500">Fecha de comunicación</dt><dd class="text-gray-800">{{ \Illuminate\Support\Carbon::parse($anulacion->fecha_generacion)->format('d/m/Y') }}</dd>
            <dt class="text-gray-500">Motivo</dt><dd class="text-gray-800">{{ $anulacion->motivo }}</dd>
            @if($anulacion->ticket)<dt class="text-gray-500">Ticket</dt><dd class="font-mono text-gray-800">{{ $anulacion->ticket }}</dd>@endif
            @if($msg)<dt class="text-gray-500">Respuesta SUNAT</dt><dd class="text-gray-800">{{ $msg }}</dd>@endif
        </dl>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr><th class="px-4 py-3">Comprobante anulado</th><th class="px-4 py-3">Motivo</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach(($anulacion->detalles ?? []) as $d)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $d['serie'] ?? '' }}-{{ $d['correlativo'] ?? '' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $d['motivo_especifico'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
