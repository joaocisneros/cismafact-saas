{{-- Las credenciales recien creadas, la unica vez que se pueden leer.

     El secreto se guarda como hash, asi que ni el sistema puede volver a
     enseñarlo: o se copia aqui, o hay que regenerarlo. Por eso ocupa lo que
     ocupa en vez de ser una linea de aviso.

     Espera $credenciales con nombre, key y secret. --}}
@php
    // Se borra al pintarlo: una recarga mas y ya no esta, que es justo lo que
    // significa «no se vuelve a mostrar».
    session()->forget('credenciales_nuevas');
@endphp
<div class="rounded-xl border-2 border-amber-300 bg-amber-50 p-5">
    <div class="flex items-start gap-3">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="min-w-0 flex-1">
            <p class="font-semibold text-amber-900">
                Copia el Secret ahora: no se vuelve a mostrar
            </p>
            <p class="mt-0.5 text-sm text-amber-800">
                Credenciales de «{{ $credenciales['nombre'] }}». Se guardan cifradas de una forma que no se
                puede revertir, así que si lo pierdes hay que generar otro —la Key seguirá siendo la misma—.
            </p>

            <div class="mt-3 space-y-2">
                @foreach(['X-Api-Key' => $credenciales['key'], 'X-Api-Secret' => $credenciales['secret']] as $etiqueta => $valor)
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-amber-700">{{ $etiqueta }}</p>
                        <div class="mt-0.5 flex items-center gap-2">
                            <code class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap rounded bg-white px-2 py-1.5 font-mono text-xs text-gray-800 ring-1 ring-amber-200">{{ $valor }}</code>
                            <button type="button"
                                    onclick="navigator.clipboard.writeText(@js($valor)); this.textContent='Copiado'; setTimeout(() => this.textContent='Copiar', 1500)"
                                    class="shrink-0 rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100">
                                Copiar
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
