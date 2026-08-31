{{-- De donde salio el dato, dicho por lo que le importa a quien mira: si esa
     consulta costo dinero o no.

     Los valores que se guardan son de dentro («proveedor», «padron»,
     «consultado antes», «ninguna») y puestos en pantalla tal cual no dicen
     nada: «ninguna» sobre todo, que sale justo cuando no se llego a consultar.

     Espera $fuente. --}}
@php
    $mapa = [
        'proveedor' => ['Se pagó', 'Salió al proveedor', 'bg-amber-50 text-amber-700'],
        'padron' => ['Gratis', 'Estaba en el padrón', 'bg-emerald-50 text-emerald-700'],
        'consultado antes' => ['Gratis', 'Ya se había consultado', 'bg-emerald-50 text-emerald-700'],
    ];
    [$texto, $detalle, $color] = $mapa[$fuente] ?? ['No se consultó', 'El número no era válido, no se llegó a preguntar', 'bg-gray-100 text-gray-500'];
@endphp
<span class="rounded px-1.5 py-0.5 text-xs {{ $color }}" title="{{ $detalle }}">{{ $texto }}</span>
