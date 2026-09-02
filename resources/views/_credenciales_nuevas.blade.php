{{-- Las credenciales recien creadas, la unica vez que se pueden leer.

     El secret se guarda como hash, asi que ni el sistema puede volver a
     enseñarlo: o se copia aqui, o hay que generar otro.

     Espera $credenciales con nombre, key, secret y la pantalla donde toca
     enseñarlo. --}}
@php
    // Solo en su pantalla.
    //
    // Vive en la sesion y no en un flash porque el formulario va por fetch, y
    // ese fetch sigue el redirect y se lo lleva antes de que la pagina se
    // recargue. El efecto secundario era que el aviso acompañaba al usuario:
    // generabas un secret en un modulo y el recuadro te salia en el siguiente
    // que abrieras, con credenciales que no eran de esa pantalla.
    $aqui = ($credenciales['pantalla'] ?? null) === request()->route()?->getName();

    // Se borra al pintarlo: una recarga mas y ya no esta, que es lo que
    // significa «no se vuelve a mostrar».
    if ($aqui) {
        session()->forget('credenciales_nuevas');
    }
@endphp

@if($aqui)
    <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-sm font-semibold text-amber-900">Copia el Secret ahora: no se vuelve a mostrar</p>
            <p class="text-xs text-amber-800">— «{{ $credenciales['nombre'] }}». Si lo pierdes, genera otro; la Key no cambia.</p>
        </div>

        <div class="mt-2 space-y-1.5">
            @foreach(['X-Api-Key' => $credenciales['key'], 'X-Api-Secret' => $credenciales['secret']] as $etiqueta => $valor)
                <div class="flex items-center gap-2">
                    <span class="w-24 shrink-0 font-mono text-xs text-amber-700">{{ $etiqueta }}</span>
                    <code class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap rounded bg-white px-2 py-1 font-mono text-xs text-gray-800 ring-1 ring-amber-200">{{ $valor }}</code>
                    <button type="button"
                            onclick="navigator.clipboard.writeText(@js($valor)); this.textContent='Copiado'; setTimeout(() => this.textContent='Copiar', 1500)"
                            class="shrink-0 rounded border border-amber-300 bg-white px-2.5 py-1 text-xs font-medium text-amber-800 hover:bg-amber-100">
                        Copiar
                    </button>
                </div>
            @endforeach
        </div>
    </div>
@endif
