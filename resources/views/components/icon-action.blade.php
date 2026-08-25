@props([
    'icon',                 // nombre del icono: ver, editar, eliminar, ...
    'label',                // tooltip y texto para lectores de pantalla
    'color' => 'slate',     // paleta de la pastilla
    'href' => null,         // si viene, se pinta como enlace en vez de boton
    'type' => 'submit',     // tipo del boton cuando no es enlace
])

@php
    // Las clases van escritas enteras a proposito: Tailwind no genera clases
    // construidas al vuelo ("bg-{$color}-50" no existiria en el CSS).
    $paletas = [
        'blue'    => 'bg-blue-50 text-blue-600 ring-blue-200 hover:bg-blue-100 hover:text-blue-700',
        'slate'   => 'bg-slate-100 text-slate-600 ring-slate-200 hover:bg-slate-200 hover:text-slate-800',
        'amber'   => 'bg-amber-50 text-amber-600 ring-amber-200 hover:bg-amber-100 hover:text-amber-700',
        'orange'  => 'bg-orange-50 text-orange-600 ring-orange-200 hover:bg-orange-100 hover:text-orange-700',
        'emerald' => 'bg-emerald-50 text-emerald-600 ring-emerald-200 hover:bg-emerald-100 hover:text-emerald-700',
        'red'     => 'bg-red-50 text-red-600 ring-red-200 hover:bg-red-100 hover:text-red-700',
        'indigo'  => 'bg-indigo-50 text-indigo-600 ring-indigo-200 hover:bg-indigo-100 hover:text-indigo-700',
        'violet'  => 'bg-violet-50 text-violet-600 ring-violet-200 hover:bg-violet-100 hover:text-violet-700',
        'cyan'    => 'bg-cyan-50 text-cyan-600 ring-cyan-200 hover:bg-cyan-100 hover:text-cyan-700',
    ];

    // Iconos macizos (Heroicons solid, 24x24). Cada entrada es una o dos rutas.
    $iconos = [
        'ver' => [
            'M12 15a3 3 0 100-6 3 3 0 000 6z',
            'M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z',
        ],
        'editar' => [
            'M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712z',
            'M19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32L19.513 8.2z',
        ],
        'eliminar' => [
            'M16.5 4.478v.227a48.816 48.816 0 013.878.512.75.75 0 11-.256 1.478l-.209-.035-1.005 13.07a3 3 0 01-2.991 2.77H8.084a3 3 0 01-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 01-.256-1.478A48.567 48.567 0 017.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 013.369 0c1.603.051 2.815 1.387 2.815 2.951zm-6.136-1.452a51.196 51.196 0 013.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 00-6 0v-.113c0-.794.609-1.428 1.364-1.452zm-.355 5.945a.75.75 0 10-1.5.058l.347 9a.75.75 0 101.499-.058l-.346-9zm5.48.058a.75.75 0 10-1.498-.058l-.347 9a.75.75 0 001.5.058l.345-9z',
        ],
        'suspender' => [
            'M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zM9 8.25a.75.75 0 01.75.75v6a.75.75 0 01-1.5 0V9A.75.75 0 019 8.25zm5.25 0a.75.75 0 01.75.75v6a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75z',
        ],
        'activar' => [
            'M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm14.024-.983a1.125 1.125 0 010 1.966l-5.603 3.113A1.125 1.125 0 019 15.113V8.887c0-.857.921-1.4 1.671-.983l5.603 3.113z',
        ],
        'soporte' => [
            'M16.5 3.75a1.5 1.5 0 011.5 1.5v13.5a1.5 1.5 0 01-1.5 1.5h-6a1.5 1.5 0 01-1.5-1.5V15a.75.75 0 00-1.5 0v3.75a3 3 0 003 3h6a3 3 0 003-3V5.25a3 3 0 00-3-3h-6a3 3 0 00-3 3V9A.75.75 0 009 9V5.25a1.5 1.5 0 011.5-1.5h6zM5.78 8.47a.75.75 0 00-1.06 0l-3 3a.75.75 0 000 1.06l3 3a.75.75 0 001.06-1.06l-1.72-1.72H15a.75.75 0 000-1.5H4.06l1.72-1.72a.75.75 0 000-1.06z',
        ],
        'renovar' => [
            'M4.755 10.059a7.5 7.5 0 0112.548-3.364l1.903 1.903h-3.183a.75.75 0 100 1.5h4.992a.75.75 0 00.75-.75V4.356a.75.75 0 00-1.5 0v3.18l-1.9-1.9A9 9 0 003.306 9.67a.75.75 0 101.45.388zm15.408 3.352a.75.75 0 00-.919.53 7.5 7.5 0 01-12.548 3.364l-1.902-1.903h3.183a.75.75 0 000-1.5H2.984a.75.75 0 00-.75.75v4.992a.75.75 0 001.5 0v-3.18l1.9 1.9a9 9 0 0015.059-4.035.75.75 0 00-.53-.918z',
        ],
        'descargar' => [
            'M12 2.25a.75.75 0 01.75.75v11.19l3.22-3.22a.75.75 0 111.06 1.06l-4.5 4.5a.75.75 0 01-1.06 0l-4.5-4.5a.75.75 0 111.06-1.06l3.22 3.22V3a.75.75 0 01.75-.75zM3 16.5a.75.75 0 01.75.75v2.25c0 .414.336.75.75.75h15a.75.75 0 00.75-.75V17.25a.75.75 0 011.5 0v2.25a2.25 2.25 0 01-2.25 2.25h-15A2.25 2.25 0 012.25 19.5v-2.25A.75.75 0 013 16.5z',
        ],
        'enviar' => [
            'M3.478 2.404a.75.75 0 00-.926.941l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.404z',
        ],
        'clave' => [
            'M15.75 1.5a6.75 6.75 0 00-6.651 7.906c.067.39-.032.717-.221.906l-6.5 6.499a3 3 0 00-.878 2.121v2.818c0 .414.336.75.75.75H6a.75.75 0 00.75-.75v-1.5h1.5A.75.75 0 009 20.5V19h1.5a.75.75 0 00.53-.22l2.658-2.658c.19-.189.517-.288.906-.22A6.75 6.75 0 1015.75 1.5zm0 3a.75.75 0 000 1.5A2.25 2.25 0 0118 8.25a.75.75 0 001.5 0 3.75 3.75 0 00-3.75-3.75z',
        ],
        'bloquear' => [
            'M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z',
        ],
        'desbloquear' => [
            'M18 1.5c2.9 0 5.25 2.35 5.25 5.25v3.75a.75.75 0 01-1.5 0V6.75a3.75 3.75 0 10-7.5 0v3a3 3 0 013 3v6.75a3 3 0 01-3 3H3.75a3 3 0 01-3-3v-6.75a3 3 0 013-3h7.5v-3c0-2.9 2.35-5.25 5.25-5.25H18z',
        ],
    ];

    $paleta = $paletas[$color] ?? $paletas['slate'];
    $rutas = $iconos[$icon] ?? $iconos['ver'];
    $clases = "inline-flex h-8 w-8 items-center justify-center rounded-lg ring-1 ring-inset transition {$paleta}";
@endphp

@if($href)
    <a href="{{ $href }}" title="{{ $label }}" {{ $attributes->merge(['class' => $clases]) }}>
        <span class="sr-only">{{ $label }}</span>
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            @foreach($rutas as $d)
                <path fill-rule="evenodd" clip-rule="evenodd" d="{{ $d }}"/>
            @endforeach
        </svg>
    </a>
@else
    <button type="{{ $type }}" title="{{ $label }}" {{ $attributes->merge(['class' => $clases]) }}>
        <span class="sr-only">{{ $label }}</span>
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            @foreach($rutas as $d)
                <path fill-rule="evenodd" clip-rule="evenodd" d="{{ $d }}"/>
            @endforeach
        </svg>
    </button>
@endif
