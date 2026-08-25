@extends('layouts.app')

@section('title', 'Anulaciones')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Anulación de comprobantes</h1>
            <p class="mt-1 text-gray-500">
                Historial de lo que has anulado ante SUNAT: facturas, boletas y notas.
            </p>
        </div>
        <button type="button"
                onclick="window.openAdminModal('{{ route('empresa.anulaciones.create') }}?fecha={{ now()->toDateString() }}', 'Anular comprobantes')"
                class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
            + Anular comprobantes
        </button>
    </div>

    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>@endif

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Referencia</th>
                    <th class="px-4 py-3">Trámite</th>
                    <th class="px-4 py-3">Comprobantes</th>
                    <th class="px-4 py-3">Enviada</th>
                    <th class="px-4 py-3">Estado SUNAT</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($anulaciones as $a)
                    @php
                        $cls = match($a['estado']) {
                            'ACEPTADO' => 'bg-green-50 text-green-700',
                            'RECHAZADO', 'ERROR' => 'bg-red-50 text-red-700',
                            'ENVIADO', 'PROCESANDO' => 'bg-blue-50 text-blue-700',
                            default => 'bg-amber-50 text-amber-700',
                        };
                    @endphp
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $a['referencia'] }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded px-2 py-0.5 text-xs {{ $a['via'] === 'Resumen de boletas' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700' }}">
                                {{ $a['via'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $a['comprobantes'] }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $a['fecha']?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3"><span class="rounded px-2 py-1 text-xs font-medium {{ $cls }}">{{ $a['estado'] }}</span></td>
                        <td class="px-4 py-3 text-right">
                            @if($a['via'] === 'Resumen de boletas')
                                <x-icon-action icon="ver" label="Ver anulación" color="blue" :href="route($a['ruta'], $a['id'])" />
                            @else
                                <x-icon-action icon="ver" label="Ver anulación" color="blue" type="button"
                                               onclick="window.openAdminModal('{{ route('empresa.anulaciones.show', $a['id']) }}?modal=1', '{{ $a['referencia'] }}')" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">No has anulado comprobantes todavía.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-500">
        Una anulación solo es efectiva cuando SUNAT la acepta. Si aparece como enviada o procesando,
        entra al detalle y usa «Consultar estado» para obtener el CDR.
    </p>
</div>
@endsection
