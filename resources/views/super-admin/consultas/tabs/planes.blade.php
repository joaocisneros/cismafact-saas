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
                Cada consulta tiene su tope y su precio dentro del plan, así que quien contrata una
                sola paga solo esa: se elige al crear la API Key. El Free es el de las llaves de
                Sandbox, y ahí el tope se pone en cada llave.
            </p>
        </div>
        <button type="button" @click="nuevo = true; plan = null"
                class="whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
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
                            {{-- «Desde», porque el precio depende de lo que se
                                 contrate: el plan entero esta en la ultima fila y
                                 cada servicio suelto, en su celda. Poner aqui el
                                 total hacia creer que era eso o nada. --}}
                            @php
                                $sueltos = $apis
                                    ->map(fn ($a) => (float) ($a->planes->firstWhere('id', $plan->id)?->pivot->precio_mensual ?? 0))
                                    ->filter(fn ($p) => $p > 0);
                            @endphp
                            <p class="mt-0.5 text-lg font-bold text-gray-900">
                                @if($plan->a_medida)
                                    A convenir
                                @elseif($sueltos->isEmpty())
                                    {{-- El importe, como en las demas columnas. Ponia
                                         «Gratis» justo debajo de un plan que ya se llama
                                         «Free»: dos veces lo mismo y ni una el precio. --}}
                                    S/ 0
                                    <span class="text-xs font-normal text-gray-400">/mes</span>
                                @else
                                    <span class="text-xs font-normal text-gray-400">desde</span>
                                    S/ {{ rtrim(rtrim(number_format($sueltos->min(), 2), '0'), '.') }}
                                    <span class="text-xs font-normal text-gray-400">/mes</span>
                                @endif
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
                                        'precios' => $apis->mapWithKeys(fn ($a) => [
                                            $a->id => (float) ($a->planes->firstWhere('id', $plan->id)?->pivot->precio_mensual ?? 0),
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
                            @php
                                $tope = $api->planes->firstWhere('id', $plan->id)?->pivot->limite_mensual ?? 0;
                                // El gratis es el de las llaves de sandbox, y esas llevan
                                // su propio tope: el del plan no llega a aplicarse nunca.
                                $mandaLaLlave = $plan->esGratis();
                            @endphp
                            <td class="border-l border-gray-200 px-4 py-5 text-center">
                                @if($mandaLaLlave)
                                    {{-- Aqui salia el numero del plan como si rigiera. En
                                         la tabla se leia «300» mientras las cinco llaves
                                         que lo tienen van con 20, y ese 300 no lo ha
                                         usado ninguna: el tope se pone al crear la llave
                                         y ese gana siempre. --}}
                                    <span class="text-sm text-gray-500">lo pone cada llave</span>
                                    <p class="mt-0.5 text-xs text-gray-400">20 por defecto</p>
                                @elseif($tope > 0)
                                    <span class="text-xl font-bold text-gray-900">{{ number_format($tope) }}</span>
                                    {{-- El precio de ese servicio suelto, que es lo que se
                                         cobra a quien contrata solo ese. Sin esto la tabla
                                         solo servia para comparar volumenes. --}}
                                    <p class="mt-0.5 text-sm font-semibold {{ $plan->a_medida ? 'text-gray-400' : 'text-gray-700' }}">
                                        {{ $plan->a_medida
                                            ? 'a convenir'
                                            : 'S/ ' . rtrim(rtrim(number_format((float) ($api->planes->firstWhere('id', $plan->id)?->pivot->precio_mensual ?? 0), 2), '0'), '.') }}
                                    </p>
                                @else
                                    <span class="text-sm text-gray-300">no incluida</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach

                {{-- Lo que paga quien se lleva todo.

                     Con el precio en cada servicio, la tabla ya responde sola a
                     las tres preguntas que se hacen al vender: cuanto es solo
                     RUC, cuanto solo DNI, y cuanto los dos. --}}
                <tr class="border-t-2 border-gray-200 bg-gray-50">
                    <td class="px-5 py-3">
                        <p class="text-sm font-semibold text-gray-900">Contratando todo</p>
                        <p class="text-xs text-gray-500">Se puede contratar cada consulta por separado</p>
                    </td>

                    @foreach($planesApi as $plan)
                        <td class="border-l border-gray-200 px-4 py-3 text-center">
                            @if($plan->esGratis())
                                <span class="text-base font-bold text-gray-400">S/ 0</span>
                                <span class="text-xs font-normal text-gray-400">/mes</span>
                            @else
                                <span class="text-base font-bold text-gray-900">
                                    {{ $plan->a_medida
                                        ? 'A convenir'
                                        : 'S/ ' . rtrim(rtrim(number_format((float) $plan->precio_mensual, 2), '0'), '.') }}
                                </span>
                                @unless($plan->a_medida)
                                    <span class="text-xs font-normal text-gray-400">/mes</span>
                                @endunless
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </section>

    {{-- Todo lo del plan en un sitio: nombre, precio y cuanto trae de cada
         consulta. Asi no queda nada a medio guardar en la tabla. --}}
    <div x-show="plan || nuevo" x-cloak
         @keydown.escape.window="plan = null; nuevo = false"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
        <div @click.outside="plan = null; nuevo = false"
             class="my-auto w-full max-w-xl overflow-hidden rounded-lg bg-white shadow-xl">

            <form method="POST"
                  :action="nuevo
                      ? '{{ route('super-admin.consultas.planes.guardar') }}'
                      : '{{ url('super-admin/consultas/planes') }}/' + (plan?.id ?? '')">
                @csrf
                <template x-if="!nuevo"><input type="hidden" name="_method" value="PUT"></template>

                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900" x-text="nuevo ? 'Nuevo plan' : 'Editar plan'"></h3>
                </div>

                {{-- En dos columnas y mas ancho.

                     Los seis campos iban uno debajo de otro en un modal
                     estrecho, y el formulario acababa mas alto que la pantalla:
                     para ver lo que incluye el plan habia que bajar, y para
                     comparar con el precio, subir otra vez. Van emparejados los
                     que se miran juntos. --}}
                <div class="space-y-4 px-5 py-4"
                     x-data="{
                         aMedida: false,
                         precios: {},
                         total() {
                             return Object.values(this.precios).reduce((s, v) => s + (Number(v) || 0), 0);
                         },
                         soles(n) { return 'S/ ' + Number(n).toFixed(2); },
                     }"
                     x-effect="
                         aMedida = plan?.a_medida ?? false;
                         precios = Object.assign({}, plan?.precios ?? {});
                     ">
                    {{-- El precio ya no se pide aqui: es la suma de lo que valga
                         cada servicio, mas abajo. Pedirlo aparte dejaba poner el
                         plan a S/39 con los servicios a cero, y entonces la
                         pantalla decia una cosa y se cobraba otra. --}}
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label for="p_nombre" class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
                            <input type="text" name="nombre" id="p_nombre" required maxlength="60"
                                   :value="plan?.nombre ?? ''"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Solo RUC">
                        </div>

                        <div>
                            <span class="mb-1 block text-sm font-medium text-gray-700">Precio</span>
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition"
                                   :class="aMedida ? 'border-blue-500 bg-blue-50/50 text-gray-900' : 'border-gray-300 text-gray-600 hover:bg-gray-50'">
                                <input type="checkbox" name="a_medida" value="1" x-model="aMedida"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                A convenir
                            </label>
                        </div>
                    </div>

                    <div>
                        <label for="p_desc" class="mb-1 block text-sm font-medium text-gray-700">
                            Descripción <span class="font-normal text-gray-400">— opcional</span>
                        </label>
                        <input type="text" name="descripcion" id="p_desc" maxlength="120"
                               :value="plan?.descripcion ?? ''"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Para quien solo consulta empresas">
                    </div>

                    {{-- Las consultas y su precio en la misma fila.

                         Iban por separado —el precio arriba, las cuotas abajo—
                         y no se veia lo que de verdad importa al armar un plan:
                         cuanto sale cada consulta. Ahora se leen juntas.

                         En un plan sin precio estos numeros no rigen: ese es el
                         de las llaves de sandbox, y cada llave lleva su propio
                         tope, que gana al del plan. --}}
                    <div class="border-t border-gray-100 pt-4">
                        <div class="mb-2 flex flex-wrap items-baseline justify-between gap-x-3">
                            <p class="text-sm font-medium text-gray-700">Qué incluye y cuánto cuesta</p>
                            <p class="text-xs text-gray-400">Un 0 en consultas deja el servicio fuera</p>
                        </div>

                        <div class="overflow-hidden rounded-lg border border-gray-200">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50 text-[11px] uppercase tracking-wide text-gray-500">
                                        <th class="px-3 py-2 text-left font-semibold">Servicio</th>
                                        <th class="px-3 py-2 text-right font-semibold">Consultas al mes</th>
                                        <th class="px-3 py-2 text-right font-semibold">Precio al mes</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100">
                                    @foreach($apis as $api)
                                        <tr>
                                            <td class="px-3 py-2 font-medium text-gray-900">{{ $api->nombre }}</td>
                                            <td class="py-1.5 pl-2 pr-1">
                                                <input type="number" min="0" max="10000000"
                                                       id="q_{{ $api->id }}" name="cuotas[{{ $api->id }}]"
                                                       :value="plan?.cuotas?.[{{ $api->id }}] ?? 0"
                                                       class="w-full rounded-md border border-gray-300 px-2.5 py-1.5 text-right text-sm tabular-nums outline-none focus:ring-2 focus:ring-blue-500">
                                            </td>
                                            <td class="py-1.5 pl-1 pr-2">
                                                <div class="flex items-center gap-1">
                                                    <span class="text-xs text-gray-400">S/</span>
                                                    {{-- Deshabilitado no se envia: un plan a convenir
                                                         no guarda precios que no rigen. --}}
                                                    <input type="number" min="0" max="99999" step="0.01"
                                                           name="precios[{{ $api->id }}]"
                                                           x-model.number="precios[{{ $api->id }}]"
                                                           :disabled="aMedida"
                                                           class="w-full rounded-md border border-gray-300 px-2.5 py-1.5 text-right text-sm tabular-nums outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400">
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                                <tfoot>
                                    <tr class="border-t border-gray-200 bg-gray-50">
                                        <td colspan="2" class="px-3 py-2 text-right text-xs text-gray-600">
                                            Contratando todo
                                        </td>
                                        <td class="px-3 py-2 text-right text-sm font-bold tabular-nums text-gray-900"
                                            x-text="aMedida ? 'A convenir' : soles(total())"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <p class="mt-2 text-xs text-gray-500" x-show="! aMedida && total() <= 0" x-cloak>
                            Un plan sin precio es el de las llaves de Sandbox, y ahí el tope se pone al
                            crear cada llave —20 por defecto—, así que estas consultas no llegarían a
                            aplicarse. Ponle precio a algún servicio para que las cuotas rijan.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 border-t border-gray-200 px-5 py-4">
                    <template x-if="!nuevo">
                        <button type="button"
                                @click="if (confirm('Se elimina el plan. Si hay llaves dentro no se podrá. ¿Continuar?')) $refs.borrar.submit()"
                                class="text-sm font-medium text-red-600 hover:underline">
                            Eliminar
                        </button>
                    </template>
                    <div class="ml-auto flex gap-2">
                        <button type="button" @click="plan = null; nuevo = false"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" class="whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
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
