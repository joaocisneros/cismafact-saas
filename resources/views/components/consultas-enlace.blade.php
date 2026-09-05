{{--
    Un enlace del menú del panel de consultas.

    Como componente y no repetido seis veces: el estado «estoy aquí» y las
    clases se escriben una vez, y añadir un módulo es una línea.
--}}
@props(['ruta', 'texto'])

@php($aqui = request()->routeIs($ruta))

<a href="{{ route($ruta) }}"
   @if($aqui) aria-current="page" @endif
   class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium
          {{ $aqui ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">{{ $slot }}</svg>
    {{ $texto }}
</a>
