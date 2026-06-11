@extends('layouts.app')

@section('title', 'Anulaciones')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Anulación de comprobantes</h1>
            <p class="text-gray-500 mt-1">Comunicación de baja ante SUNAT (facturas y notas). Las boletas se anulan por Resumen Diario.</p>
        </div>
        <a href="{{ route('empresa.anulaciones.create') }}"
           class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
            + Nueva anulación
        </a>
    </div>

    @if(session('success'))<div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>@endif

    <div class="overflow-x-auto bg-white border border-gray-200 rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Correlativo (RA)</th>
                    <th class="px-4 py-3">Fecha documentos</th>
                    <th class="px-4 py-3">Comprobantes</th>
                    <th class="px-4 py-3">Estado SUNAT</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($anulaciones as $a)
                    @php
                        $cls = match($a->estado_sunat) {
                            'ACEPTADO' => 'bg-green-50 text-green-700',
                            'RECHAZADO','ERROR' => 'bg-red-50 text-red-700',
                            'ENVIADO','PROCESANDO' => 'bg-blue-50 text-blue-700',
                            default => 'bg-amber-50 text-amber-700',
                        };
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">RA-{{ \Illuminate\Support\Carbon::parse($a->fecha_generacion)->format('Ymd') }}-{{ $a->correlativo }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ \Illuminate\Support\Carbon::parse($a->fecha_referencia)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ count($a->detalles ?? []) }}</td>
                        <td class="px-4 py-3"><span class="rounded px-2 py-1 text-xs font-medium {{ $cls }}">{{ $a->estado_sunat }}</span></td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('empresa.anulaciones.show', $a->id) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No has anulado comprobantes todavía.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $anulaciones->links() }}</div>
</div>
@endsection
