{{-- La ficha de una credencial, igual en los cuatro sitios donde sale.

     Habia cuatro: dos de facturacion y dos de RUC y DNI, cada una con su
     ancho, sus colores y su forma de colocar lo mismo —la URL, la clave, el
     secret y cuatro cifras—. Se parecian lo justo para despistar.

     Da la estructura, no los datos: dos de ellas se pintan desde el servidor y
     las otras dos con Alpine sobre un objeto que llega en el clic, asi que
     cada una llena sus huecos como puede.

     Huecos: titulo, subtitulo, estado, etiqueta, credenciales, nota, metricas
     y acciones. --}}
@props([
    'ancho' => 'max-w-2xl',
    // Sin marco propio cuando ya va dentro de una ventana que lo tiene.
    // Con el, se veian tres bordes metidos uno dentro de otro para
    // enseñar tres lineas de texto.
    'suelta' => true,
])

@php
    $marco = $suelta
        ? 'my-auto w-full ' . $ancho . ' overflow-hidden rounded-xl bg-white shadow-xl'
        : 'w-full';
@endphp

<div {{ $attributes->merge(['class' => $marco]) }}>

    {{-- Cabecera: de quien es y si sirve ahora mismo. --}}
    <div class="flex items-start justify-between gap-3 {{ $suelta ? 'border-b border-gray-200 px-6 py-4' : 'pb-4' }}">
        <div class="min-w-0">
            <h3 class="truncate text-base font-semibold text-gray-900">{{ $titulo }}</h3>
            @isset($subtitulo)
                <p class="mt-0.5 truncate text-xs text-gray-500">{{ $subtitulo }}</p>
            @endisset
        </div>

        @isset($estado)
            <div class="shrink-0">{{ $estado }}</div>
        @endisset
    </div>

    <div class="space-y-5 {{ $suelta ? 'px-6 py-5' : '' }}">

        {{-- Lo que se viene a buscar: las tres lineas que se copian. --}}
        <div class="overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/40">
            <div class="flex items-center justify-between gap-2 border-b border-indigo-200 bg-indigo-50 px-4 py-2.5">
                <span class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Credenciales</span>
                @isset($etiqueta)
                    <div class="shrink-0">{{ $etiqueta }}</div>
                @endisset
            </div>

            <div class="space-y-2 px-4 py-3">
                {{ $credenciales }}
            </div>

            @isset($nota)
                <p class="border-t border-indigo-100 px-4 py-2 text-xs text-gray-500">{{ $nota }}</p>
            @endisset
        </div>

        {{-- Las cifras, en una fila: son de apoyo y no compiten con lo de arriba. --}}
        @isset($metricas)
            <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-lg bg-gray-200 text-center sm:grid-cols-4">
                {{ $metricas }}
            </dl>
        @endisset
    </div>

    @isset($acciones)
        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-200 {{ $suelta ? 'bg-gray-50 px-6 py-3' : 'mt-5 pt-4' }}">
            {{ $acciones }}
        </div>
    @endisset
</div>
