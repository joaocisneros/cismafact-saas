@props([
    'name' => 'password',
    'id' => null,
    'placeholder' => 'Mínimo 8 caracteres',
    'required' => false,
    'minlength' => 8,
    'error' => null,
])

@php
    $id = $id ?? $name;
    // El nombre del campo sirve para pintar el borde en rojo si ese campo falló.
    $campoConError = $error ?? $name;
@endphp

{{--
    Campo de contraseña con el ojo para mostrarla u ocultarla.

    Estaba repetido en cada formulario, y en cada uno un poco distinto: unos con
    el texto "Ver", otros sin nada. Aquí vive una sola vez.

    El estado es propio de cada campo (x-data local), así que se puede poner dos
    veces en la misma pantalla sin que se destapen a la vez.
--}}
<div class="relative" x-data="{ verClave: false }">
    <input :type="verClave ? 'text' : 'password'"
           id="{{ $id }}"
           name="{{ $name }}"
           @if($required) required @endif
           @if($minlength) minlength="{{ $minlength }}" @endif
           placeholder="{{ $placeholder }}"
           {{ $attributes->merge([
                'class' => 'w-full rounded-lg border py-2.5 pl-4 pr-10 outline-none transition focus:ring-2 focus:ring-blue-500 '
                    . ($errors->has($campoConError) ? 'border-red-400' : 'border-gray-300 focus:border-blue-500'),
           ]) }}>

    <button type="button" @click="verClave = ! verClave" tabindex="-1"
            :aria-label="verClave ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 transition hover:text-gray-600">
        <svg x-show="! verClave" class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 010-.64C3.42 7.5 7.36 4.5 12 4.5s8.58 3 9.96 7.18a1 1 0 010 .64C20.58 16.5 16.64 19.5 12 19.5s-8.58-3-9.96-7.18z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
        <svg x-show="verClave" x-cloak class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22C2.94 9.28 2.2 10.5 1.82 11.4a1 1 0 000 .64C3.2 16.2 7.14 19.2 11.78 19.2c1.6 0 3.11-.36 4.46-1"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.23 6.23A10.45 10.45 0 0112 4.8c4.64 0 8.58 3 9.96 7.18a1 1 0 010 .64 12.3 12.3 0 01-2.6 3.9"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 9.88a3 3 0 104.24 4.24M3 3l18 18"/>
        </svg>
    </button>
</div>
