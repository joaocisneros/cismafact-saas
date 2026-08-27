<div class="p-5">

    {{-- Cabecera: de quién es y si sirve --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h4 class="truncate text-lg font-semibold text-gray-900">{{ $apiKey->name }}</h4>
            <p class="truncate text-sm text-gray-500">{{ $apiKey->company->razon_social ?? 'Sin empresa' }}</p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $apiKey->active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
            <span class="h-1.5 w-1.5 rounded-full {{ $apiKey->active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
            {{ $apiKey->active ? 'Activa' : 'Bloqueada' }}
        </span>
    </div>

    {{-- Lo que se le entrega al programador. Es a lo que se viene, así que va
         primero y ocupa el sitio principal. La Key salía además arriba, en el
         resumen: repetida y a nadie le servía dos veces. --}}
    @php
        // El secreto en claro solo se guarda para los tokens de prueba, que es
        // el dueno quien los reparte. En una empresa real lo tiene su cliente y
        // aqui no se ensena: se muestran URL y Key, que son lo que hace falta
        // para identificar y depurar.
        $esSandbox = (bool) $apiKey->company?->es_demo;

        $credenciales = ['URL base' => url('/api'), 'X-Api-Key' => $apiKey->key];
        if ($esSandbox) {
            $credenciales['X-Api-Secret'] = $apiKey->plain_secret;
        }
    @endphp

        <div class="mt-5 overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/40">
            <div class="flex items-center justify-between border-b border-indigo-200 bg-indigo-50 px-4 py-2.5">
                <span class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Credenciales</span>
                <span class="rounded px-2 py-0.5 text-xs font-medium {{ $esSandbox ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                    {{ $esSandbox ? 'SUNAT beta' : 'Empresa real' }}
                </span>
            </div>

            <div class="divide-y divide-indigo-100">
                @foreach ($credenciales as $label => $valor)
                    <div class="flex items-center gap-3 px-4 py-3">
                        <span class="w-24 shrink-0 text-xs font-medium text-indigo-900/70">{{ $label }}</span>
                        <code class="min-w-0 flex-1 break-all rounded bg-white px-2 py-1.5 font-mono text-xs text-gray-800 ring-1 ring-indigo-100">{{ $valor ?: '—' }}</code>
                        @if ($valor)
                            {{-- navigator.clipboard solo existe en HTTPS o en localhost, y el
                                 panel corre en un dominio .test: ahi el boton no hacia nada. Se
                                 intenta primero y, si no esta, se copia con un textarea
                                 temporal, que funciona en cualquier sitio. --}}
                            <button type="button"
                                    onclick="(function (b, t) {
                                        function ok() {
                                            b.textContent = 'Copiado';
                                            b.className = b.dataset.ok;
                                            setTimeout(function () { b.textContent = 'Copiar'; b.className = b.dataset.normal; }, 1500);
                                        }
                                        function alternativa() {
                                            var c = document.createElement('textarea');
                                            c.value = t; c.style.position = 'fixed'; c.style.opacity = '0';
                                            document.body.appendChild(c); c.select();
                                            try { document.execCommand('copy'); ok(); }
                                            catch (e) { b.textContent = 'Copia a mano'; }
                                            document.body.removeChild(c);
                                        }
                                        var moderno = navigator.clipboard ? window.isSecureContext : false;
                                        if (moderno) { navigator.clipboard.writeText(t).then(ok).catch(alternativa); }
                                        else { alternativa(); }
                                    })(this, @js($valor))"
                                    data-normal="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
                                    data-ok="shrink-0 rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm"
                                    class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                        @endif
                    </div>
                @endforeach

                @unless ($esSandbox)
                    <div class="px-4 py-2.5 text-xs text-gray-500">
                        El secreto solo lo tiene el cliente. Si lo perdió, hay que generarle una credencial nueva.
                    </div>
                @endunless
            </div>
        </div>

    {{-- Consumo, en una línea: son datos de apoyo, no el motivo de abrir esto --}}
    <dl class="mt-5 grid grid-cols-3 gap-px overflow-hidden rounded-lg bg-gray-200 text-center">
        @foreach ([
            'Llamadas' => number_format($totalUsage),
            'Último uso' => $apiKey->last_used_at?->diffForHumans() ?? 'Nunca',
            'Creada' => $apiKey->created_at->format('d/m/Y'),
        ] as $label => $valor)
            <div class="bg-white px-3 py-2.5">
                <dt class="text-xs text-gray-500">{{ $label }}</dt>
                <dd class="mt-0.5 truncate text-sm font-semibold text-gray-900">{{ $valor }}</dd>
            </div>
        @endforeach
    </dl>

    {{-- La advertencia de qué pasa al bloquear ya la da el confirm; repetirla
         aquí en un párrafo solo alargaba la ventana. --}}
    <div class="mt-5 flex justify-end border-t border-gray-200 pt-4">
        <form method="POST"
              action="{{ route('super-admin.api-global.toggle-key', $apiKey) }}"
              data-success-message="API Key {{ $apiKey->active ? 'bloqueada' : 'activada' }} correctamente."
              onsubmit="return confirm('{{ $apiKey->active ? 'Se rechazarán todas las peticiones con esta credencial. ¿Bloquearla?' : '¿Activar esta credencial?' }}')">
            @csrf
            <button type="submit"
                    class="rounded-md px-4 py-2 text-sm font-medium text-white {{ $apiKey->active ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                {{ $apiKey->active ? 'Bloquear' : 'Activar' }}
            </button>
        </form>
    </div>

</div>
