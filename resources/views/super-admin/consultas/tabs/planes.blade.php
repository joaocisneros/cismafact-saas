{{-- Una tabla y no un bloque por servicio.
     Con "API RUC" arriba y "API DNI" debajo, cada uno con sus planes, parecia
     que se elige uno U otro. Puestos los planes como columnas y las consultas
     como filas se ve que un plan incluye las dos, cuanto de cada una, y que un
     0 la deja fuera: eso es lo que permite vender "Solo RUC", "Solo DNI" y
     "Completo" sin inventar ningun mecanismo nuevo.

     La tabla solo enseña. Todo lo de un plan —nombre, precio y cuotas— se
     edita en su modal: tener aqui unos campos sueltos con un boton de guardar
     al final obligaba a recordar que quedaba algo sin guardar. --}}
<div x-data="{ plan: null, nuevo: false }">

    @php
        $tonos = [
            'text-green-600', 'text-blue-600', 'text-purple-600',
            'text-orange-600', 'text-teal-600', 'text-pink-600',
        ];
    @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Qué incluye cada plan</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Al mes. Una consulta sin cuota queda fuera del plan: así se arman paquetes como «Solo RUC».
            </p>
        </div>
        <button type="button" @click="nuevo = true; plan = null"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
            Nuevo plan
        </button>
    </div>

    <section class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full">
            <thead>
                <tr>
                    {{-- Esta celda cruza "los servicios" con "los planes", asi
                         que dice de que va la tabla en vez de repetir la
                         palabra de la primera columna. --}}
                    <th class="w-64 border-b border-gray-200 px-5 py-4 text-left align-middle">
                        <p class="text-sm font-semibold text-gray-900">Servicios</p>
                        <p class="mt-0.5 text-xs font-normal text-gray-500">Consultas incluidas <strong class="font-semibold">al mes</strong></p>
                    </th>

                    @foreach($planesApi as $i => $plan)
                        <th class="group border-b-2 border-l border-gray-200 px-5 py-4 text-center">
                            <p class="text-sm font-bold {{ $tonos[$i % count($tonos)] }}">{{ $plan->nombre }}</p>
                            <p class="mt-0.5 text-lg font-bold text-gray-900">
                                {{ $plan->a_medida ? 'A convenir' : 'S/ ' . rtrim(rtrim(number_format((float) $plan->precio_mensual, 2), '0'), '.') }}
                                @unless($plan->a_medida)
                                    <span class="text-xs font-normal text-gray-400">/mes</span>
                                @endunless
                            </p>

                            <button type="button"
                                    @click="plan = {{ Illuminate\Support\Js::from([
                                        'id' => $plan->id,
                                        'nombre' => $plan->nombre,
                                        'descripcion' => $plan->descripcion,
                                        'precio_mensual' => (float) $plan->precio_mensual,
                                        'a_medida' => (bool) $plan->a_medida,
                                        'cuotas' => $apis->mapWithKeys(fn ($a) => [
                                            $a->id => $a->planes->firstWhere('id', $plan->id)?->pivot->limite_mensual ?? 0,
                                        ]),
                                    ]) }}; nuevo = false"
                                    class="mt-1 text-xs font-normal text-gray-400 opacity-0 transition group-hover:opacity-100 hover:text-blue-600 hover:underline focus:opacity-100">
                                Editar
                            </button>
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @foreach($apis as $api)
                    <tr>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg
                                             {{ $api->slug === 'ruc' ? 'bg-green-50 text-green-600' : 'bg-purple-50 text-purple-600' }}">
                                    @if($api->slug === 'ruc')
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    @else
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">{{ $api->nombre }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ $api->descripcion ?: '—' }}</p>
                                </div>
                            </div>
                        </td>

                        @foreach($planesApi as $plan)
                            @php $tope = $api->planes->firstWhere('id', $plan->id)?->pivot->limite_mensual ?? 0; @endphp
                            <td class="border-l border-gray-200 px-4 py-5 text-center">
                                @if($tope > 0)
                                    <span class="text-xl font-bold text-gray-900">{{ number_format($tope) }}</span>
                                @else
                                    <span class="text-sm text-gray-300">no incluida</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    {{-- Todo lo del plan en un sitio: nombre, precio y cuanto trae de cada
         consulta. Asi no queda nada a medio guardar en la tabla. --}}
    <div x-show="plan || nuevo" x-cloak
         @keydown.escape.window="plan = null; nuevo = false"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-6">
        <div @click.outside="plan = null; nuevo = false"
             class="my-auto w-full max-w-md rounded-xl bg-white shadow-xl">

            <form method="POST"
                  :action="nuevo
                      ? '{{ route('super-admin.consultas.planes.guardar') }}'
                      : '{{ url('super-admin/consultas/planes') }}/' + (plan?.id ?? '')">
                @csrf
                <template x-if="!nuevo"><input type="hidden" name="_method" value="PUT"></template>

                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900" x-text="nuevo ? 'Nuevo plan' : 'Editar plan'"></h3>
                </div>

                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label for="p_nombre" class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
                        <input type="text" name="nombre" id="p_nombre" required maxlength="60"
                               :value="plan?.nombre ?? ''"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Solo RUC">
                    </div>

                    <div>
                        <label for="p_desc" class="mb-1 block text-sm font-medium text-gray-700">Descripción</label>
                        <input type="text" name="descripcion" id="p_desc" maxlength="120"
                               :value="plan?.descripcion ?? ''"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Para quien solo consulta empresas">
                    </div>

                    <div x-data="{ aMedida: false }" x-effect="aMedida = plan?.a_medida ?? false">
                        <label for="p_precio" class="mb-1 block text-sm font-medium text-gray-700">Precio al mes</label>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">S/</span>
                            <input type="number" name="precio_mensual" id="p_precio" required min="0" max="99999" step="0.01"
                                   :value="plan?.precio_mensual ?? 0" :disabled="aMedida"
                                   class="w-32 rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400">
                        </div>
                        <label class="mt-2 flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="a_medida" value="1" x-model="aMedida"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            Precio a convenir
                        </label>
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <p class="mb-2 text-sm font-medium text-gray-700">Qué incluye al mes</p>
                        <div class="space-y-2">
                            @foreach($apis as $api)
                                <div class="flex items-center justify-between gap-3">
                                    <label for="q_{{ $api->id }}" class="text-sm text-gray-700">{{ $api->nombre }}</label>
                                    <input type="number" min="0" max="10000000"
                                           id="q_{{ $api->id }}" name="cuotas[{{ $api->id }}]"
                                           :value="plan?.cuotas?.[{{ $api->id }}] ?? 0"
                                           class="w-32 rounded-lg border border-gray-300 px-3 py-2 text-right text-sm outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Un 0 deja esa consulta fuera del plan.</p>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 border-t border-gray-100 px-5 py-4">
                    <template x-if="!nuevo">
                        <button type="button"
                                @click="if (confirm('Se elimina el plan. Si hay llaves dentro no se podrá. ¿Continuar?')) $refs.borrar.submit()"
                                class="text-sm font-medium text-red-600 hover:underline">
                            Eliminar
                        </button>
                    </template>
                    <div class="ml-auto flex gap-2">
                        <button type="button" @click="plan = null; nuevo = false"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                            Guardar
                        </button>
                    </div>
                </div>
            </form>

            <form x-ref="borrar" method="POST" class="hidden"
                  :action="'{{ url('super-admin/consultas/planes') }}/' + (plan?.id ?? '')">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>

</div>
