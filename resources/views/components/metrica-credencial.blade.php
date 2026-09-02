{{-- Una de las cifras del pie de la ficha.

     Con su icono y su color: en fila, cuatro números seguidos y en gris se
     leen como una tabla y hay que ir a la etiqueta para saber cuál es cuál.

     El tono avisa: la caducidad en ámbar, porque es el único de los cuatro
     que obliga a hacer algo antes de que llegue. --}}
@props([
    'titulo',
    'tono' => 'slate',
    // Resalta el valor, no solo el icono: para la fecha de caducidad.
    'destacado' => false,
])

@php
    $tonos = [
        'slate' => 'bg-gray-100 text-gray-600',
        'violet' => 'bg-violet-50 text-violet-600',
        'blue' => 'bg-blue-50 text-blue-600',
        'green' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
    ];
@endphp

<div class="flex items-center gap-3 bg-white px-4 py-3">
    @isset($icono)
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $tonos[$tono] ?? $tonos['slate'] }}">
            {{ $icono }}
        </span>
    @endisset

    <div class="min-w-0">
        <dt class="truncate text-xs text-gray-500">{{ $titulo }}</dt>
        <dd class="truncate text-sm font-semibold {{ $destacado ? 'text-amber-600' : 'text-gray-900' }}">{{ $slot }}</dd>
    </div>
</div>
