{{-- El mes en una linea, dentro de la propia tarjeta.

     Antes eran cuatro tarjetas grandes por pestaña, encima de las cuatro de la
     cabecera: dos filas de tarjetas seguidas, y las dos primeras diciendo
     «Consultas este mes» con cifras distintas. El dato sigue estando, pero sin
     competir con el resumen de arriba.

     Espera $r (el resumen del origen) y $que (como llamar a lo que se cuenta).
     $lider es opcional: la empresa que mas busca, que antes ocupaba una tabla
     entera para decir un nombre y un numero. --}}
@php
    $ahorro = $r['total'] ? round($r['en_casa'] / $r['total'] * 100) : 0;
@endphp

@php $coste = $coste ?? false; @endphp

<div class="flex flex-wrap items-center gap-x-5 gap-y-1 border-b border-gray-100 bg-gray-50/60 px-5 py-2.5 text-xs">
    <span class="text-gray-500">
        <span class="font-semibold text-gray-900">{{ number_format($r['total']) }}</span>
        {{ $que }} este mes
    </span>

    <span class="text-gray-500">
        <span class="font-semibold {{ $r['proveedor'] ? 'text-amber-700' : 'text-gray-400' }}">{{ number_format($r['proveedor']) }}</span>
        {{ $coste ? "con costo" : "del proveedor" }}
    </span>

    <span class="text-gray-500">
        <span class="font-semibold {{ $r['en_casa'] ? 'text-emerald-700' : 'text-gray-400' }}">{{ number_format($r['en_casa']) }}</span>
        {{ $coste ? "sin costo" : "de casa" }}
        @if($r['en_casa'])
            <span class="text-gray-400">({{ $ahorro }}%)</span>
        @endif
    </span>

    @if(($r['de_prueba'] ?? 0) > 0)
        <span class="text-gray-500">
            <span class="font-semibold text-blue-700">{{ number_format($r['de_prueba']) }}</span>
            de prueba
        </span>
    @endif

    <span class="text-gray-500">
        <span class="font-semibold {{ $r['fallidas'] ? 'text-red-700' : 'text-gray-400' }}">{{ number_format($r['fallidas']) }}</span>
        con error
    </span>

    @if($r['ms_medio'])
        <span class="text-gray-400">{{ number_format($r['ms_medio']) }} ms de media</span>
    @endif

    @if(! empty($lider) && $lider->total)
        <span class="text-gray-500">
            la que más busca:
            <span class="font-medium text-gray-900">{{ $lider->empresa ?? 'sin empresa' }}</span>
            <span class="text-gray-400">({{ number_format($lider->total) }})</span>
        </span>
    @endif
</div>
