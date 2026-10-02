{{-- Medidor legible incluso cuando el consumo todavía es menor al 1 %. --}}
@props(['servicio'])

@php
    $porcentaje = min(100, max(0, (float) $servicio['porcentaje']));
    $anchoVisible = $porcentaje > 0 ? max(1.5, $porcentaje) : 0;
    $esRuc = strtolower($servicio['slug']) === 'ruc';
@endphp

<div class="min-w-[210px] flex-1 rounded-xl border {{ $esRuc ? 'border-blue-200 bg-blue-50' : 'border-violet-200 bg-violet-50' }} p-4">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider {{ $esRuc ? 'text-blue-700' : 'text-violet-700' }}">Consultas {{ strtoupper($servicio['slug']) }}</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums text-gray-900">{{ number_format($servicio['usadas']) }}</p>
            <p class="text-xs text-gray-500">utilizadas de {{ number_format($servicio['tope']) }}</p>
        </div>
        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold tabular-nums {{ $esRuc ? 'text-blue-700' : 'text-violet-700' }}">
            {{ number_format($porcentaje, $porcentaje < 1 ? 2 : 1) }}%
        </span>
    </div>
    <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-white/80">
        <div class="h-full rounded-full {{ $esRuc ? 'bg-blue-500' : 'bg-violet-500' }}" style="width: {{ $anchoVisible }}%"></div>
    </div>
    <div class="mt-2 flex items-center justify-between text-xs">
        <span class="text-gray-500">Disponible</span>
        <span class="font-semibold tabular-nums text-gray-700">{{ number_format($servicio['restantes']) }}</span>
    </div>
</div>
