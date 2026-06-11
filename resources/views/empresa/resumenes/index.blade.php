@extends('layouts.app')

@section('title', 'Resúmenes diarios')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Resumen Diario de Boletas</h1>
            <p class="text-gray-500 mt-1">Anula boletas ya aceptadas mediante el resumen diario (RC) ante SUNAT.</p>
        </div>
        <a href="{{ route('empresa.resumenes.create') }}"
           class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
            + Anular boletas
        </a>
    </div>

    @if(session('success'))<div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>@endif

    <div class="overflow-x-auto bg-white border border-gray-200 rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Resumen (RC)</th>
                    <th class="px-4 py-3">Fecha boletas</th>
                    <th class="px-4 py-3">Boletas</th>
                    <th class="px-4 py-3">Estado SUNAT</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($resumenes as $r)
                    @php
                        $cls = match($r->estado_sunat) {
                            'ACEPTADO' => 'bg-green-50 text-green-700',
                            'RECHAZADO','ERROR' => 'bg-red-50 text-red-700',
                            'PROCESANDO','ENVIADO' => 'bg-blue-50 text-blue-700',
                            default => 'bg-amber-50 text-amber-700',
                        };
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">RC-{{ \Illuminate\Support\Carbon::parse($r->fecha_generacion)->format('Ymd') }}-{{ $r->correlativo }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ \Illuminate\Support\Carbon::parse($r->fecha_resumen)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ count($r->detalles ?? []) }}</td>
                        <td class="px-4 py-3"><span class="rounded px-2 py-1 text-xs font-medium {{ $cls }}">{{ $r->estado_sunat }}</span></td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('empresa.resumenes.show', $r->id) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No has generado resúmenes todavía.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $resumenes->links() }}</div>
</div>
@endsection
