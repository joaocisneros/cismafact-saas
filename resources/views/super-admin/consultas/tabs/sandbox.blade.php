{{-- Las llaves de prueba, aparte de las que cobran.
     Un sandbox devuelve datos de ejemplo y no gasta cuota, asi que quien lo usa
     esta integrando, no consumiendo. Mezclarlas con las de produccion enturbia
     las dos preguntas: "a quien le cobro" y "quien esta probando". --}}
<div x-data="{ nueva: false, detalle: null, llave: null }">

    @if($creada = session('llave_creada'))
        <div x-data="{ abierto: true }" x-show="abierto" x-cloak
             @keydown.escape.window="abierto = false"
             class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
            <div class="my-auto w-full max-w-2xl overflow-hidden rounded-lg bg-white shadow-xl">
                {{-- La misma cabecera que el modal de «Ver»: es la misma llave
                     mirada en dos momentos, y con dos formas distintas parecia
                     otra pantalla. --}}
                <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <div class="min-w-0">
                        <h3 class="truncate text-base font-semibold text-gray-900">{{ $creada['nombre'] }}</h3>
                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ ($creada['regenerada'] ?? false) ? 'Secreto nuevo. El anterior ya no vale.' : 'Recién creada.' }}
                            Cópiala ahora:
                            <strong class="text-gray-700">el secreto no se vuelve a mostrar.</strong>
                        </p>
                    </div>
                    <span class="ml-auto inline-flex shrink-0 items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span> Sandbox
                    </span>
                    <button type="button" @click="abierto = false" aria-label="Cerrar"
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
                            <span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Datos reales</span>
                        </div>
                        <div class="divide-y divide-indigo-100">
                            @foreach(['URL base' => url('/api/consultas'), 'X-Api-Key' => $creada['clave'], 'X-Api-Secret' => $creada['secreto']] as $etiqueta => $valor)
                                <div class="flex items-center gap-3 px-4 py-2">
                                    <span class="w-24 shrink-0 text-xs font-medium text-indigo-900/70">{{ $etiqueta }}</span>
                                    <code class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap rounded bg-white px-2 py-1.5 font-mono text-xs text-gray-800 ring-1 ring-indigo-100">{{ $valor }}</code>
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
                    {{ $sandbox->count() }} en total. Consultan datos reales, con un tope corto para que el cliente vea que el servicio sirve.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                {{-- Solo sale si hay algo que limpiar: un boton de borrar
                     siempre a la vista, sin nada que borrar, es un susto de
                     mas cada vez que se entra. --}}
                @php $vencidas = $sandbox->filter->vencida(); @endphp
                @if($vencidas->isNotEmpty())
                    <form method="POST" action="{{ route('super-admin.consultas.llaves.limpiar-vencidas') }}"
                          onsubmit="return confirm('Se eliminarán {{ $vencidas->count() }} llave(s) de prueba ya vencida(s). Se borran también las consultas que hicieron. ¿Seguir?')">
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
                <table class="min-w-full table-fixed divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        {{-- «Para quién» encabezaba la llave, y el titular iba
                             colgado de «Da acceso a», que es de los servicios.
                             Cada dato pasa a su columna. --}}
                        <tr>
                            {{-- Reparto fijo. Antes solo «Empresa» podia crecer,
                                 asi que acumulaba todo el sobrante en un hueco
                                 en blanco y las otras cinco quedaban apiladas
                                 contra el borde derecho. --}}
                            <th class="w-1/4 px-5 py-3">Empresa</th>
                            <th class="w-1/12 whitespace-nowrap px-5 py-3">Servicios</th>
                            <th class="w-1/12 whitespace-nowrap px-5 py-3">Plan</th>
                            {{-- Cuanto lleva y cuanto tiene. El plan no vale
                                 aqui: todas las de prueba llevan el mismo y lo
                                 que manda es el tope de cada una. --}}
                            <th class="w-1/12 whitespace-nowrap px-5 py-3 text-right">Consultas</th>
                            <th class="w-1/6 whitespace-nowrap px-5 py-3">Último uso</th>
                            <th class="w-1/6 whitespace-nowrap px-5 py-3">Vigencia</th>
                            <th class="w-1/6 whitespace-nowrap px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($sandbox as $l)
                            <tr class="{{ $l->activa && ! $l->vencida() ? '' : 'bg-gray-50/60' }}">
                                {{-- Manda de quien es. El nombre de la llave va
                                     debajo: hace falta para distinguir cuando un
                                     mismo titular tiene mas de una, pero no es lo
                                     que se busca al mirar la lista. --}}
                                <td class="px-5 py-3">
                                    <p class="truncate font-medium text-gray-900" title="{{ $l->nombreDelTitular() }}">{{ $l->nombreDelTitular() }}</p>
                                    <p class="truncate text-xs text-gray-500" title="{{ $l->nombre }}">
                                        {{ $l->nombre }}
                                        <span class="font-mono text-gray-400">· {{ Str::limit($l->clave, 16) }}</span>
                                    </p>
                                </td>

                                <td class="whitespace-nowrap px-5 py-3 text-gray-600">
                                    {{ collect($l->servicios)->map(fn ($s) => strtoupper($s))->join(' y ') }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-3">
                                    @if($l->plan)
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ $l->plan->nombre }}</span>
                                    @else
                                        <span class="text-xs text-gray-300">—</span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-5 py-3 text-right">
                                    @php
                                        // Lo gastado este mes, que es lo que cuenta contra
                                        // el tope: el total historico no dice si le queda.
                                        $delMes = $l->consumo()
                                            ->where('exito', true)
                                            ->where('created_at', '>=', now()->startOfMonth())
                                            ->count();
                                        $tope = $l->tope_pruebas;
                                    @endphp
                                    <span class="font-medium text-gray-900">{{ number_format($delMes) }}</span>
                                    @if($tope)
                                        <span class="text-gray-400">/ {{ number_format($tope) }}</span>
                                        @if($delMes >= $tope)
                                            <p class="text-xs text-red-600">sin cuota</p>
                                        @endif
                                    @else
                                        <p class="text-xs text-gray-400">sin tope</p>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-5 py-3 text-gray-600">
                                    {{ $l->ultimo_uso_en?->diffForHumans() ?? 'nunca' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-3">
                                    @if(! $l->activa)
                                        <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Bloqueada</span>
                                    @elseif($l->vencida())
                                        <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Vencida</span>
                                        <p class="mt-0.5 text-xs text-gray-400">el {{ $l->expira_en->format('d/m/Y') }}</p>
                                    @elseif($l->expira_en)
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Activa</span>
                                        <p class="mt-0.5 text-xs text-gray-400">
                                            hasta el {{ $l->expira_en->format('d/m/Y') }}
                                        </p>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Activa</span>
                                        <p class="mt-0.5 text-xs text-gray-400">sin caducidad</p>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-5 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <x-icon-action icon="ver" label="Ver credenciales" color="blue" type="button"
                                                       @click="detalle = {{ Illuminate\Support\Js::from([
                                                           'id' => $l->id,
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

                                        {{-- El secreto no se puede recuperar: si el
                                             cliente lo pierde, la unica salida era
                                             borrar la llave y crear otra, y con
                                             ella se iba su historial. --}}
                                        <form method="POST" action="{{ route('super-admin.consultas.llaves.regenerar', $l) }}"
                                              onsubmit="return confirm('Se genera un secreto nuevo para «{{ $l->nombre }}».

El actual deja de funcionar en cuanto se guarde, así que hay que pasarle el nuevo al programador. La clave (X-Api-Key) no cambia.

¿Seguir?')">
                                            @csrf
                                            <x-icon-action icon="renovar" label="Generar un secreto nuevo" color="slate" />
                                        </form>

                                        <form method="POST" action="{{ route('super-admin.consultas.llaves.alternar', $l) }}">
                                            @csrf
                                            <x-icon-action :icon="$l->activa ? 'bloquear' : 'desbloquear'"
                                                           :label="$l->activa ? 'Bloquear' : 'Desbloquear'"
                                                           :color="$l->activa ? 'amber' : 'emerald'" />
                                        </form>

                                        <form method="POST" action="{{ route('super-admin.consultas.llaves.borrar', $l) }}"
                                              onsubmit="return confirm('Se elimina «{{ $l->nombre }}» y las {{ $l->consumo_count }} consultas que hizo.

El historial no se puede recuperar.

¿Continuar?')">
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
            · Consultan de verdad, con tope: sirven para que el cliente compruebe el servicio antes de contratar.
        </div>
    </div>

    {{-- Alta rapida: en pruebas no hace falta plan ni cuota, solo a quien es y
         a que da acceso. Preguntar lo demas seria papeleo para nada. --}}
    <div x-show="nueva || llave" x-cloak @keydown.escape.window="nueva = false; llave = null"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
        <div @click.outside="nueva = false; llave = null" class="my-auto w-full max-w-md overflow-hidden rounded-lg bg-white shadow-xl">
            {{-- El mismo modal crea y edita: son los mismos campos, y tener dos
                 formularios iguales acaba con uno de los dos desactualizado. --}}
            <form method="POST" x-ref="formulario"
                  :action="nueva
                      ? '{{ route('super-admin.consultas.llaves.guardar') }}'
                      : '{{ url('super-admin/consultas/llaves') }}/' + (llave?.id ?? '')">
                @csrf
                <template x-if="! nueva"><input type="hidden" name="_method" value="PUT"></template>
                <input type="hidden" name="entorno" value="sandbox">
                <input type="hidden" name="api_plan_id" value="{{ $planesApi->first()?->id }}">

                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900" x-text="nueva ? 'Nueva llave de prueba' : 'Editar llave de prueba'"></h3>
                    <p class="mt-0.5 text-xs text-gray-500">Consulta datos reales, con el tope que le pongas.</p>
                </div>

                {{-- Tres campos: a quien, a que le da acceso y cuantas
                     consultas le tocan.

                     Sin desplegable de empresas: las consultas se venden aparte
                     de la facturacion, y quien pide una llave de prueba suele
                     ser un programador, no una empresa dada de alta.

                     El nombre no se pregunta: se arma con el titular. --}}
                <div class="space-y-3 px-5 py-4"
                     x-data="{
                         marcados: @js($apis->pluck('slug')),
                         alterna(slug, on) {
                             this.marcados = on
                                 ? [...new Set([...this.marcados, slug])]
                                 : this.marcados.filter(s => s !== slug);
                         },
                     }"
                     x-effect="
                         if (llave) {
                             marcados = llave.servicios ?? [];
                         } else if (! nueva) {
                             /* El modal se oculta pero no se destruye, asi que
                                sus campos guardaban lo ultimo escrito. Se vacia
                                al cerrarse. */
                             marcados = @js($apis->pluck('slug'));
                             $refs.formulario?.reset();
                         }
                     ">

                    <input type="hidden" name="titular_tipo" value="externo">

                    <div>
                        {{-- «Empresa», que es como se llama la columna de la
                             lista: el mismo dato dicho igual en los dos sitios. --}}
                        <label for="s_titular" class="mb-1 block text-sm font-medium text-gray-900">
                            Empresa
                        </label>
                        <input type="text" name="titular" id="s_titular" maxlength="120" required
                               :value="llave?.titular ?? ''"
                               placeholder="Ej.: Contables SAC (o el programador)"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    {{-- Cada consulta, una tarjeta que se marca entera. Con dos
                         casillas sueltas habia que saberse de memoria que trae
                         cada una; aqui lo dice debajo, que es lo mismo que se
                         lee en la pestaña de planes. --}}
                    <div>
                        <p class="mb-1 block text-sm font-medium text-gray-900">¿A qué le da acceso?</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach($apis as $api)
                                <label class="flex cursor-pointer gap-2 rounded-lg border px-2.5 py-2 transition"
                                       :class="marcados.includes('{{ $api->slug }}') ? 'border-blue-500 bg-blue-50/50' : 'border-gray-200 hover:bg-gray-50'">
                                    <input type="checkbox" name="servicios[]" value="{{ $api->slug }}"
                                           :checked="marcados.includes('{{ $api->slug }}')"
                                           @change="alterna('{{ $api->slug }}', $event.target.checked)"
                                           class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-gray-900">{{ $api->nombre }}</span>
                                        @if($api->descripcion)
                                            <span class="block truncate text-xs text-gray-500" title="{{ $api->descripcion }}">{{ $api->descripcion }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- El tope es lo que separa esta llave de una de pago:
                         consulta de verdad, pero poco. Sin el, una llave gratis
                         seria un plan de pago regalado. --}}
                    <div>
                        <label for="s_tope" class="mb-1 block text-sm font-medium text-gray-900">
                            Cuántas consultas le das
                        </label>
                        <div class="flex items-center gap-2">
                            {{-- Vacio, con el 20 solo de sugerencia: si viene sin
                                 poner, el sistema le da veinte. --}}
                            <input type="number" name="tope_pruebas" id="s_tope" min="1" max="5000"
                                   placeholder="20" :value="llave?.tope_pruebas ?? ''"
                                   class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm text-gray-500">al mes</span>
                        </div>
                    </div>

                    {{-- En dias, no en fecha: nadie sabe de memoria en que dia
                         cae dentro de un mes. Al editar si va la fecha, que ahi
                         se busca un dia concreto. --}}
                    <div x-show="! llave">
                        <label for="s_dias" class="mb-1 block text-sm font-medium text-gray-900">
                            Caduca en
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="expira_dias" id="s_dias" min="1" max="365" value="30"
                                   class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                            <span class="text-sm text-gray-500">días — vacío para que no caduque</span>
                        </div>
                    </div>

                    {{-- Sin campo de nombre: se arma con el titular al crear y
                         se conserva al editar. Enseñarlo aqui invitaba a
                         cambiarlo, y el nombre es lo unico por lo que se
                         distingue una llave en la lista. --}}
                    <template x-if="llave">
                        <div class="space-y-4 border-t border-gray-100 pt-4">
                            <div>
                                <label for="s_expira" class="mb-1 block text-sm font-medium text-gray-900">Caduca el</label>
                                <input type="date" name="expira_en" id="s_expira"
                                       :value="llave?.expira_en ?? ''"
                                       min="{{ now()->addDay()->format('Y-m-d') }}"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                                <p class="mt-1 text-xs text-gray-500">Vacío = sin caducidad.</p>
                            </div>
                        </div>
                    </template>
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
        <div @click.outside="detalle = null" class="my-auto w-full max-w-2xl overflow-hidden rounded-lg bg-white shadow-xl">
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
                        <span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Datos reales</span>
                    </div>
                    <div class="divide-y divide-indigo-100">
                        <div class="flex items-center gap-3 px-4 py-2">
                            <span class="w-24 shrink-0 text-xs font-medium text-indigo-900/70">URL base</span>
                            <code class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap rounded bg-white px-2 py-1.5 font-mono text-xs text-gray-800 ring-1 ring-indigo-100">{{ url('/api/consultas') }}</code>
                            <button type="button" onclick="window.copyCompanyCredential(this, @js(url('/api/consultas')))"
                                    class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                        </div>
                        <div class="flex items-center gap-3 px-4 py-2">
                            <span class="w-24 shrink-0 text-xs font-medium text-indigo-900/70">X-Api-Key</span>
                            <code class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap rounded bg-white px-2 py-1.5 font-mono text-xs text-gray-800 ring-1 ring-indigo-100" x-text="detalle?.clave"></code>
                            <button type="button" @click="window.copyCompanyCredential($el, detalle?.clave)"
                                    class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                        </div>
                        {{-- Tapado hasta que se pide. Se guarda cifrado, asi
                             que el sistema puede leerlo, pero no viaja con la
                             pagina: solo se trae el que se pulsa. --}}
                        <div class="flex items-center gap-3 px-4 py-2"
                             x-data="{ visible: false, valor: null, cargando: false,
                                 async mostrar() {
                                     if (this.visible) { this.visible = false; return; }
                                     if (! this.valor) {
                                         this.cargando = true;
                                         try {
                                             const r = await fetch('{{ url('super-admin/consultas/llaves') }}/' + detalle.id + '/secreto', {
                                                 headers: { 'Accept': 'application/json' },
                                             });
                                             this.valor = (await r.json()).secreto;
                                         } catch (e) {
                                             this.valor = null;
                                         } finally {
                                             this.cargando = false;
                                         }
                                     }
                                     this.visible = !! this.valor;
                                 },
                             }"
                             x-effect="detalle; visible = false; valor = null">
                            <span class="w-24 shrink-0 text-xs font-medium text-indigo-900/70">X-Api-Secret</span>

                            <code class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap rounded bg-white px-2 py-1.5 font-mono text-xs ring-1 ring-indigo-100"
                                  :class="visible ? 'text-gray-800' : 'text-gray-400'">
                                <span x-show="! visible">··················<span x-text="detalle?.pista"></span></span>
                                <span x-show="visible" x-text="valor"></span>
                            </code>

                            <button type="button" @click="mostrar()" :disabled="cargando"
                                    class="shrink-0 rounded-md border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50"
                                    x-text="cargando ? '…' : (visible ? 'Ocultar' : 'Mostrar')"></button>

                            <button type="button" x-show="visible" x-cloak
                                    @click="window.copyCompanyCredential($el, valor)"
                                    class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                        </div>
                        <div class="px-4 py-2 text-xs text-gray-500">
                            No sale con la página: se pide solo al pulsar «Mostrar».
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
