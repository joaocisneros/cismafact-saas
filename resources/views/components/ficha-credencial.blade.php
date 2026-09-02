{{-- La ficha de una credencial, igual en los cuatro sitios donde sale.

     Había cuatro: dos de facturación y dos de RUC y DNI, cada una con su
     ancho, sus colores y su forma de colocar lo mismo —la URL, la clave, el
     secret y cuatro cifras—. Se parecían lo justo para despistar.

     Da la estructura, no los datos: dos se pintan desde el servidor y las
     otras dos con Alpine sobre un objeto que llega en el clic, así que cada
     una llena sus huecos como puede.

     Huecos: icono, titulo, subtitulo, estado, etiqueta, intro, credenciales,
     metricas y acciones. --}}
@props([
    'ancho' => 'max-w-3xl',
    // Sin marco propio cuando ya va dentro de una ventana que lo tiene: con
    // él se veían tres bordes metidos uno dentro de otro.
    'suelta' => true,
])

@php
    $marco = $suelta
        ? 'my-auto w-full ' . $ancho . ' overflow-hidden rounded-2xl bg-white shadow-xl'
        : 'w-full';
@endphp

<div {{ $attributes->merge(['class' => $marco]) }}>

    {{-- Cabecera: de quién es, y si sirve ahora mismo. --}}
    <div class="flex items-start gap-4 {{ $suelta ? 'border-b border-gray-100 px-6 py-5' : 'pb-5' }}">
        @isset($icono)
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                {{ $icono }}
            </span>
        @endisset

        <div class="min-w-0 flex-1">
            <h3 class="truncate text-lg font-semibold tracking-tight text-gray-900">{{ $titulo }}</h3>
            @isset($subtitulo)
                <p class="mt-0.5 truncate text-sm text-gray-500">{{ $subtitulo }}</p>
            @endisset
        </div>

        @isset($estado)
            <div class="shrink-0">{{ $estado }}</div>
        @endisset
    </div>

    <div class="space-y-4 {{ $suelta ? 'px-6 py-5' : '' }}">

        {{-- Lo que se viene a buscar. Va en su propio panel de color: es lo
             único de la ficha que se copia, y así se distingue de las cifras
             de apoyo sin tener que leerlas. --}}
        <div class="overflow-hidden rounded-xl border border-indigo-200 bg-indigo-50/30">
            <div class="flex items-center justify-between gap-3 border-b border-indigo-200 bg-indigo-50 px-4 py-2.5">
                <span class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Credenciales</span>
                @isset($etiqueta)
                    <div class="shrink-0">{{ $etiqueta }}</div>
                @endisset
            </div>

            @isset($intro)
                <p class="border-b border-indigo-100 bg-white/60 px-4 py-2.5 text-xs text-gray-600">{{ $intro }}</p>
            @endisset

            <div class="divide-y divide-indigo-100">
                {{ $credenciales }}
            </div>
        </div>

        {{-- Las cifras, de apoyo: no compiten con el panel de arriba. --}}
        @isset($metricas)
            <dl class="grid grid-cols-2 divide-gray-100 overflow-hidden rounded-xl border border-gray-200 sm:grid-cols-4 sm:divide-x">
                {{ $metricas }}
            </dl>
        @endisset
    </div>

    @isset($acciones)
        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 {{ $suelta ? 'bg-gray-50/70 px-6 py-4' : 'mt-5 pt-4' }}">
            {{ $acciones }}
        </div>
    @endisset
</div>
