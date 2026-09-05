{{--
    El medidor de consumo de un servicio.

    Anillo y no barra: dos barras largas ocupaban media pantalla para decir lo
    mismo, y aquí lo que importa se lee de un vistazo —cuántas llevas y cuántas
    te quedan.
--}}
@props(['servicio'])

@php
    $radio = 42;
    $vuelta = 2 * M_PI * $radio;
    // Lo pintado es lo gastado: el hueco es lo que queda.
    $pintado = $vuelta * min(1, $servicio['porcentaje'] / 100);
@endphp

<div class="text-center">
    <svg width="104" height="104" viewBox="0 0 104 104" class="mx-auto mb-2">
        <circle cx="52" cy="52" r="{{ $radio }}" fill="none" stroke-width="9" class="stroke-gray-100"/>
        <circle cx="52" cy="52" r="{{ $radio }}" fill="none" stroke-width="9" stroke-linecap="round"
                class="stroke-emerald-500 origin-center -rotate-90"
                stroke-dasharray="{{ round($vuelta, 1) }}"
                stroke-dashoffset="{{ round($vuelta - $pintado, 1) }}"/>
        <text x="52" y="50" text-anchor="middle" class="fill-gray-900 text-lg font-semibold tabular-nums">{{ number_format($servicio['usadas']) }}</text>
        <text x="52" y="64" text-anchor="middle" class="fill-gray-400 text-[9px]">de {{ number_format($servicio['tope']) }}</text>
    </svg>
    <p class="text-sm font-semibold text-gray-900">{{ strtoupper($servicio['slug']) }}</p>
    <p class="text-xs text-gray-500 tabular-nums">quedan {{ number_format($servicio['restantes']) }}</p>
</div>
