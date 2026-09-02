{{-- Una linea de la ficha: la etiqueta, el valor y su boton.

     Las cuatro fichas las escribian a mano y ninguna coincidia: cambiaba el
     ancho de la etiqueta, el tamaño de la letra y hasta el color del boton. --}}
@props([
    'etiqueta',
])

<div class="flex items-center gap-2">
    <span class="w-24 shrink-0 text-xs font-medium text-indigo-900/70">{{ $etiqueta }}</span>

    <code class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap rounded border border-indigo-100 bg-white px-2.5 py-1.5 font-mono text-xs text-gray-800">{{ $slot }}</code>

    @isset($boton)
        {{ $boton }}
    @endisset
</div>
