{{--
    El medidor de consumo de un servicio.

    Anillo y no barra: dos barras largas ocupaban media pantalla para decir lo
    mismo, y aquí lo que importa se lee de un vistazo —cuántas llevas y cuántas
    te quedan.
--}}
@props(['servicio'])

@php
    $radio = 58;
    $vuelta = 2 * M_PI * $radio;
    // Lo pintado es lo gastado: el hueco es lo que queda.
    $pintado = $vuelta * min(1, $servicio['porcentaje'] / 100);
@endphp

<div class="text-center">
    <svg width="140" height="140" viewBox="0 0 140 140" class="mx-auto mb-3">
        <circle cx="70" cy="70" r="{{ $radio }}" fill="none" stroke-width="12" class="stroke-gray-100"/>
        <circle cx="70" cy="70" r="{{ $radio }}" fill="none" stroke-width="12" stroke-linecap="round"
                class="stroke-emerald-500 origin-center -rotate-90"
                stroke-dasharray="{{ round($vuelta, 1) }}"
                stroke-dashoffset="{{ round($vuelta - $pintado, 1) }}"/>
        <text x="70" y="68" text-anchor="middle" class="fill-gray-900 text-3xl font-semibold tabular-nums">{{ number_format($servicio['usadas']) }}</text>
        <text x="70" y="88" text-anchor="middle" class="fill-gray-400 text-xs">de {{ number_format($servicio['tope']) }}</text>
    </svg>
    <p class="text-base font-semibold text-gray-900">{{ strtoupper($servicio['slug']) }}</p>
    <p class="text-sm text-gray-500 tabular-nums">quedan {{ number_format($servicio['restantes']) }}</p>
</div>
