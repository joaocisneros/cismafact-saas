{{-- Una de las cifras del pie de la ficha. --}}
@props([
    'titulo',
])

<div class="bg-white px-3 py-2.5">
    <dt class="text-xs text-gray-500">{{ $titulo }}</dt>
    <dd class="mt-0.5 text-sm font-semibold text-gray-900">{{ $slot }}</dd>
</div>
