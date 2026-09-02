{{-- Una línea del panel de credenciales: qué es, para qué sirve, el valor y
     su botón.

     Las cuatro fichas la escribían a mano y ninguna coincidía: cambiaba el
     ancho de la etiqueta, el tamaño de la letra y hasta el color del botón.

     La descripción no es adorno: «X-Api-Key» y «X-Api-Secret» se parecen
     demasiado, y quien recibe las credenciales por primera vez no sabe cuál
     de las dos puede enseñar y cuál no. --}}
@props([
    'etiqueta',
    'descripcion' => null,
    // Cada línea con su tono, para distinguirlas de un vistazo.
    'tono' => 'indigo',
])

@php
    $tonos = [
        'indigo' => 'bg-indigo-100 text-indigo-600',
        'violet' => 'bg-violet-100 text-violet-600',
        'amber' => 'bg-amber-100 text-amber-600',
    ];
@endphp

<div class="flex flex-col gap-3 bg-white/70 px-4 py-3 sm:flex-row sm:items-center">

    <div class="flex min-w-0 items-center gap-3 sm:w-60 sm:shrink-0">
        @isset($icono)
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $tonos[$tono] ?? $tonos['indigo'] }}">
                {{ $icono }}
            </span>
        @endisset

        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-gray-900">{{ $etiqueta }}</p>
            @if($descripcion)
                <p class="truncate text-xs text-gray-500">{{ $descripcion }}</p>
            @endif
        </div>
    </div>

    <code class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap rounded-lg border border-indigo-100 bg-white px-3 py-2 font-mono text-xs text-gray-800">{{ $slot }}</code>

    @isset($boton)
        <div class="flex shrink-0 items-center gap-2">{{ $boton }}</div>
    @endisset
</div>
