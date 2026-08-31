{{-- De donde salio el dato de esa consulta.

     Los valores que se guardan son de dentro («proveedor», «padron»,
     «consultado antes», «ninguna») y puestos en pantalla tal cual no dicen
     nada: «ninguna» sobre todo, que sale justo cuando no se llego a consultar.

     Sirve para dos cosas segun donde se mire. En el consumo externo dice si
     esa consulta costo dinero. En el interno no se le cobra a nadie, asi que
     ahi lo que explica es el tiempo: 9 ms es que salio de casa; si hubiera
     ido al proveedor serian cientos.

     Espera $fuente. Con $coste (solo en el externo) lo dice en dinero. --}}
@php
    $coste = $coste ?? false;

    $mapa = [
        'proveedor' => [$coste ? 'Con costo' : 'Proveedor', 'Hubo que salir al proveedor', 'bg-amber-50 text-amber-700'],
        'padron' => [$coste ? 'Sin costo' : 'Padrón', 'Estaba en el padrón', 'bg-emerald-50 text-emerald-700'],
        'consultado antes' => [$coste ? 'Sin costo' : 'Ya guardada', 'Ya se había consultado antes', 'bg-emerald-50 text-emerald-700'],
        // Ni costo ni salio de casa: es un dato inventado para pruebas.
        'modo prueba' => ['Prueba sandbox', 'Llave de sandbox: el dato es de ejemplo, no es real', 'bg-blue-50 text-blue-700'],
    ];

    // «invalido»: el numero no valia, no se pregunto a nadie.
    // «ninguna»: el numero valia, pero no se pudo traer la ficha.
    $porDefecto = $fuente === 'invalido'
        ? ['—', 'El número no era válido, no se llegó a preguntar', 'bg-gray-100 text-gray-500']
        : ['—', 'Se preguntó, pero no se pudo traer la ficha', 'bg-gray-100 text-gray-500'];

    [$texto, $detalle, $color] = $mapa[$fuente] ?? $porDefecto;
@endphp
<span class="rounded px-1.5 py-0.5 text-xs {{ $color }}" title="{{ $detalle }}">{{ $texto }}</span>
