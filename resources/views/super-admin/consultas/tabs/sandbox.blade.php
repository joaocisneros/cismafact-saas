{{-- Las llaves de prueba, aparte de las que cobran.
     Un sandbox devuelve datos de ejemplo y no gasta cuota, asi que quien lo usa
     esta integrando, no consumiendo. Mezclarlas con las de produccion enturbia
     las dos preguntas: "a quien le cobro" y "quien esta probando". --}}
<div x-data="{ nueva: false, detalle: null, llave: null }">

    @if($creada = session('llave_creada'))
        <div x-data="{ abierto: true }" x-show="abierto" x-cloak
             @keydown.escape.window="abierto = false"
             class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
            <div class="my-auto w-full max-w-lg overflow-hidden rounded-lg bg-white shadow-xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900">«{{ $creada['nombre'] }}» creada</h3>
                    <p class="mt-0.5 text-xs text-gray-500">
                        Cópiala ahora: <strong class="text-gray-700">el secreto no se vuelve a mostrar.</strong>
                    </p>
                </div>

                <div class="p-4">
                    <div class="overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/40">
                        <div class="border-b border-indigo-200 bg-indigo-50 px-4 py-2.5">
                            <span class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Credenciales</span>
                        </div>
                        <div class="divide-y divide-indigo-100">
                            @foreach(['URL base' => url('/api/consultas'), 'X-Api-Key' => $creada['clave'], 'X-Api-Secret' => $creada['secreto']] as $etiqueta => $valor)
                                <div class="flex items-center gap-3 px-4 py-2.5">
                                    <span class="w-24 shrink-0 text-xs font-medium text-indigo-900/70">{{ $etiqueta }}</span>
                                    <code class="min-w-0 flex-1 break-all rounded bg-white px-2 py-1.5 font-mono text-xs text-gray-800 ring-1 ring-indigo-100">{{ $valor }}</code>
                                    <button type="button" onclick="window.copyCompanyCredential(this, @js($valor))"
                                            class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-200 px-5 py-4">
                    <button type="button" @click="abierto = false"
                            class="whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                        Ya la copié
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Llaves de prueba</h2>
                <p class="mt-0.5 text-xs text-gray-500">
                    {{ $sandbox->count() }} en total. Devuelven datos de ejemplo, no salen a internet y no gastan cuota.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                {{-- Solo sale si hay algo que limpiar: un boton de borrar
                     siempre a la vista, sin nada que borrar, es un susto de
                     mas cada vez que se entra. --}}
                @php $vencidas = $sandbox->filter->vencida(); @endphp
                @if($vencidas->isNotEmpty())
                    <form method="POST" action="{{ route('super-admin.consultas.llaves.limpiar-vencidas') }}"
                          onsubmit="return confirm('Se eliminarán {{ $vencidas->count() }} llave(s) de prueba ya vencida(s). Las consultas que hicieron se conservan en el historial. ¿Seguir?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="whitespace-nowrap rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50">
                            Eliminar vencidas ({{ $vencidas->count() }})
                        </button>
                    </form>
                @endif

                <button type="button" @click="nueva = true"
                        class="whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                    Nueva llave de prueba
                </button>
            </div>
        </div>

        @if($sandbox->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="text-sm text-gray-500">Todavía no hay ninguna llave de prueba.</p>
                <p class="mt-1 text-xs text-gray-400">Dale una a quien esté integrando, para que no gaste sus consultas.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Para quién</th>
                            <th class="px-5 py-3">Da acceso a</th>
                            <th class="px-5 py-3">Llamadas</th>
                            <th class="px-5 py-3">Último uso</th>
                            <th class="px-5 py-3">Caduca</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($sandbox as $l)
                            <tr class="{{ $l->activa && ! $l->vencida() ? '' : 'bg-gray-50/60' }}">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-gray-900">{{ $l->nombre }}</p>
                                    <p class="truncate font-mono text-xs text-gray-400">{{ Str::limit($l->clave, 26) }}</p>
                                </td>

                                <td class="px-5 py-3 text-gray-600">
                                    {{ collect($l->servicios)->map(fn ($s) => strtoupper($s))->join(' y ') }}
                                    <p class="text-xs text-gray-400">{{ $l->nombreDelTitular() }}</p>
                                </td>

                                <td class="px-5 py-3 font-medium text-gray-900">{{ number_format($l->consumo_count) }}</td>

                                <td class="px-5 py-3 text-gray-600">
                                    {{ $l->ultimo_uso_en?->diffForHumans() ?? 'nunca' }}
                                </td>

                                <td class="px-5 py-3">
                                    @if(! $l->activa)
                                        <span class="text-xs font-medium text-red-600">Bloqueada</span>
                                    @elseif($l->vencida())
                                        <span class="text-xs font-medium text-red-600">Expiró el {{ $l->expira_en->format('d/m/Y') }}</span>
                                    @elseif($l->expira_en)
                                        <span class="text-xs text-gray-600">{{ $l->expira_en->format('d/m/Y') }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">Sin caducidad</span>
                                    @endif
                                </td>

                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <x-icon-action icon="ver" label="Ver credenciales" color="blue" type="button"
                                                       @click="detalle = {{ Illuminate\Support\Js::from([
                                                           'nombre' => $l->nombre,
                                                           'titular' => $l->nombreDelTitular(),
                                                           'clave' => $l->clave,
                                                           'pista' => $l->secreto_pista,
                                                           'servicios' => collect($l->servicios)->map(fn ($x) => strtoupper($x))->join(' y '),
                                                           'llamadas' => $l->consumo_count,
                                                           'ultimo_uso' => $l->ultimo_uso_en?->format('d/m/Y H:i'),
                                                           'creada' => $l->created_at->format('d/m/Y'),
                                                           'expira' => $l->expira_en?->format('d/m/Y'),
                                                       ]) }}" />

                                        {{-- Faltaba: sin esto no habia forma de
                                             cambiarle la caducidad a una llave ya
                                             creada, que es justo lo que se toca
                                             cuando alguien sigue integrando. --}}
                                        <x-icon-action icon="editar" label="Editar" color="slate" type="button"
                                                       @click="llave = {{ Illuminate\Support\Js::from([
                                                           'id' => $l->id,
                                                           'nombre' => $l->nombre,
                                                           'titular' => $l->titular,
                                                           'servicios' => (array) $l->servicios,
                                                           'expira_en' => $l->expira_en?->format('Y-m-d'),
                                                       ]) }}" />

                                        <form method="POST" action="{{ route('super-admin.consultas.llaves.alternar', $l) }}">
                                            @csrf
                                            <x-icon-action :icon="$l->activa ? 'bloquear' : 'desbloquear'"
                                                           :label="$l->activa ? 'Bloquear' : 'Desbloquear'"
                                                           :color="$l->activa ? 'amber' : 'emerald'" />
                                        </form>

                                        <form method="POST" action="{{ route('super-admin.consultas.llaves.borrar', $l) }}"
                                              onsubmit="return confirm('Se elimina «{{ $l->nombre }}». ¿Continuar?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-icon-action icon="eliminar" label="Eliminar" color="red" />
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="border-t border-gray-200 px-5 py-3 text-xs text-gray-500">
            Dirección que necesita el programador:
            <code class="ml-1 rounded bg-gray-100 px-2 py-0.5 font-mono text-gray-700">{{ url('/api/consultas') }}</code>
            · En sandbox la respuesta es siempre de ejemplo, así que sirve para probar la integración sin gastar nada.
        </div>
    </div>

    {{-- Alta rapida: en pruebas no hace falta plan ni cuota, solo a quien es y
         a que da acceso. Preguntar lo demas seria papeleo para nada. --}}
    <div x-show="nueva || llave" x-cloak @keydown.escape.window="nueva = false; llave = null"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
        <div @click.outside="nueva = false; llave = null" class="my-auto w-full max-w-md overflow-hidden rounded-lg bg-white shadow-xl">
            {{-- El mismo modal crea y edita: son los mismos campos, y tener dos
                 formularios iguales acaba con uno de los dos desactualizado. --}}
            <form method="POST"
                  :action="nueva
                      ? '{{ route('super-admin.consultas.llaves.guardar') }}'
                      : '{{ url('super-admin/consultas/llaves') }}/' + (llave?.id ?? '')">
                @csrf
                <template x-if="! nueva"><input type="hidden" name="_method" value="PUT"></template>
                <input type="hidden" name="entorno" value="sandbox">
                <input type="hidden" name="titular_tipo" value="externo">
                <input type="hidden" name="api_plan_id" value="{{ $planesApi->first()?->id }}">

                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900" x-text="nueva ? 'Nueva llave de prueba' : 'Editar llave de prueba'"></h3>
                    <p class="mt-0.5 text-xs text-gray-500">Devuelve datos de ejemplo. No gasta cuota ni sale a internet.</p>
                </div>

                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label for="s_nombre" class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
                        <input type="text" name="nombre" id="s_nombre" required maxlength="80"
                               :value="llave?.nombre ?? ''"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Pruebas - ERP de Contables SAC">
                    </div>

                    <div>
                        <label for="s_titular" class="mb-1 block text-sm font-medium text-gray-700">Para quién</label>
                        <input type="text" name="titular" id="s_titular" required maxlength="120"
                               :value="llave?.titular ?? ''"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Nombre del programador o de su empresa">
                    </div>

                    <div>
                        <p class="mb-1 block text-sm font-medium text-gray-700">A qué da acceso</p>
                        <div class="flex flex-wrap gap-4">
                            @foreach($apis as $api)
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="servicios[]" value="{{ $api->slug }}"
                                           :checked="nueva || (llave?.servicios ?? []).includes('{{ $api->slug }}')"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    {{ $api->nombre }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label for="s_expira" class="mb-1 block text-sm font-medium text-gray-700">
                            Caduca el <span class="font-normal text-gray-400">— opcional</span>
                        </label>
                        <input type="date" name="expira_en" id="s_expira"
                               :value="llave?.expira_en ?? ''"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">
                            Útil aquí: una llave de prueba que caduca sola no se queda viva para siempre.
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-4">
                    <button type="button" @click="nueva = false; llave = null"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                            x-text="nueva ? 'Crear' : 'Guardar cambios'"></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Detalle, con el mismo panel que el resto del sistema. --}}
    <div x-show="detalle" x-cloak @keydown.escape.window="detalle = null"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
        <div @click.outside="detalle = null" class="my-auto w-full max-w-lg overflow-hidden rounded-lg bg-white shadow-xl">
            <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-4">
                <div class="min-w-0">
                    <h3 class="truncate text-base font-semibold text-gray-900" x-text="detalle?.nombre"></h3>
                    <p class="mt-0.5 truncate text-xs text-gray-500">
                        <span x-text="detalle?.titular"></span> · <span x-text="detalle?.servicios"></span>
                    </p>
                </div>
                <span class="ml-auto inline-flex shrink-0 items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span> Sandbox
                </span>
                <button type="button" @click="detalle = null" aria-label="Cerrar"
                        class="shrink-0 rounded-md p-2 text-gray-500 hover:bg-gray-100">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-4">
                <div class="overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/40">
                    <div class="flex items-center justify-between border-b border-indigo-200 bg-indigo-50 px-4 py-2.5">
                        <span class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Credenciales</span>
                        <span class="rounded bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Datos de ejemplo</span>
                    </div>
                    <div class="divide-y divide-indigo-100">
                        <div class="flex items-center gap-3 px-4 py-2.5">
                            <span class="w-24 shrink-0 text-xs font-medium text-indigo-900/70">URL base</span>
                            <code class="min-w-0 flex-1 break-all rounded bg-white px-2 py-1.5 font-mono text-xs text-gray-800 ring-1 ring-indigo-100">{{ url('/api/consultas') }}</code>
                            <button type="button" onclick="window.copyCompanyCredential(this, @js(url('/api/consultas')))"
                                    class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                        </div>
                        <div class="flex items-center gap-3 px-4 py-2.5">
                            <span class="w-24 shrink-0 text-xs font-medium text-indigo-900/70">X-Api-Key</span>
                            <code class="min-w-0 flex-1 break-all rounded bg-white px-2 py-1.5 font-mono text-xs text-gray-800 ring-1 ring-indigo-100" x-text="detalle?.clave"></code>
                            <button type="button" @click="window.copyCompanyCredential($el, detalle?.clave)"
                                    class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                        </div>
                        <div class="flex items-center gap-3 px-4 py-2.5">
                            <span class="w-24 shrink-0 text-xs font-medium text-indigo-900/70">X-Api-Secret</span>
                            <code class="min-w-0 flex-1 rounded bg-white px-2 py-1.5 font-mono text-xs text-gray-400 ring-1 ring-indigo-100">
                                ··················<span x-text="detalle?.pista"></span>
                            </code>
                        </div>
                        <div class="px-4 py-2 text-xs text-gray-500">
                            El secreto solo lo tiene el cliente. Si lo perdió, hay que crearle otra API Key.
                        </div>
                    </div>
                </div>

                <dl class="mt-4 grid grid-cols-4 gap-px overflow-hidden rounded-lg bg-gray-200 text-center">
                    <div class="bg-white px-3 py-2.5">
                        <dt class="text-xs text-gray-500">Llamadas</dt>
                        <dd class="mt-0.5 truncate text-sm font-semibold text-gray-900" x-text="(detalle?.llamadas ?? 0).toLocaleString('es-PE')"></dd>
                    </div>
                    <div class="bg-white px-3 py-2.5">
                        <dt class="text-xs text-gray-500">Último uso</dt>
                        <dd class="mt-0.5 truncate text-sm font-semibold text-gray-900" x-text="detalle?.ultimo_uso ?? 'Nunca'"></dd>
                    </div>
                    <div class="bg-white px-3 py-2.5">
                        <dt class="text-xs text-gray-500">Creada</dt>
                        <dd class="mt-0.5 truncate text-sm font-semibold text-gray-900" x-text="detalle?.creada"></dd>
                    </div>
                    <div class="bg-white px-3 py-2.5">
                        <dt class="text-xs text-gray-500">Caduca</dt>
                        <dd class="mt-0.5 truncate text-sm font-semibold text-gray-900" x-text="detalle?.expira ?? 'No caduca'"></dd>
                    </div>
                </dl>
            </div>

            <div class="flex justify-end border-t border-gray-200 px-5 py-4">
                <button type="button" @click="detalle = null"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

</div>
