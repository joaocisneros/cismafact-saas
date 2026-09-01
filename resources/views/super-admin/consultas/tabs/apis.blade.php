{{-- Las llaves con las que se entra a las consultas.
     Aparte de las de emision a proposito: quien compra consultas puede no
     facturar, y bloquearle una cosa no debe cortarle la otra. Un mismo titular
     quiere varias —una por sistema suyo— para poder cortar una sin dejar las
     demas sin servicio. --}}
<div x-data="{ llave: null, nueva: false, detalle: null }">

    @if($nueva = session('llave_creada'))
        {{-- En modal y no como cartel: el secreto solo se enseña una vez y hay
             que copiarlo ahora, asi que tiene que cortar el paso en vez de
             quedar arriba del todo empujando la pagina. --}}
        <div x-data="{ abierto: true }" x-show="abierto" x-cloak
             @keydown.escape.window="abierto = false"
             class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
            <div class="my-auto w-full max-w-lg overflow-hidden rounded-lg bg-white shadow-xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900">«{{ $nueva['nombre'] }}» creada</h3>
                    <p class="mt-0.5 text-xs text-gray-500">
                        Cópiala ahora: <strong class="text-gray-700">el secreto no se vuelve a mostrar.</strong>
                        Si se pierde, se genera otra.
                    </p>
                </div>

                <div class="p-5">
                    <div class="overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/40">
                        <div class="border-b border-indigo-200 bg-indigo-50 px-4 py-2.5">
                            <span class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Credenciales</span>
                        </div>
                        <div class="divide-y divide-indigo-100">
                            @foreach(['URL base' => url('/api/consultas'), 'X-Api-Key' => $nueva['clave'], 'X-Api-Secret' => $nueva['secreto']] as $etiqueta => $valor)
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

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">API Keys</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Las de las consultas de RUC y DNI. Son distintas de las de emitir: bloquear una no afecta a la facturación.
            </p>
        </div>
        <button type="button" @click="nueva = true; llave = null"
                class="whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
            Nueva API Key
        </button>
    </div>

    <section class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        @forelse($llaves as $l)
            @php
                $estado = $l->estado();
                $tope = $l->plan
                    ? $apis->whereIn('slug', (array) $l->servicios)->sum(fn ($a) => $a->limiteDelPlan($l->api_plan_id))
                    : 0;
                $pct = $tope > 0 ? min(100, round($l->usadas_mes / $tope * 100)) : 0;
            @endphp

            <div class="flex flex-wrap items-center gap-4 px-5 py-4 {{ $estado === 'activa' ? '' : 'bg-gray-50/60' }}">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold text-gray-900">{{ $l->nombre }}</p>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium
                            @if($estado === 'activa') bg-green-50 text-green-700
                            @elseif($estado === 'sandbox') bg-blue-50 text-blue-700
                            @elseif($estado === 'vencida') bg-amber-50 text-amber-700
                            @else bg-red-50 text-red-700 @endif">
                            {{ ['activa' => '● Activa', 'sandbox' => 'Sandbox', 'vencida' => 'Vencida', 'bloqueada' => 'Bloqueada'][$estado] }}
                        </span>
                    </div>

                    <p class="mt-0.5 text-xs text-gray-500">
                        {{ collect($l->servicios)->map(fn ($s) => strtoupper($s))->join(' y ') }}
                        · {{ $l->nombreDelTitular() }}
                        · {{ $l->plan?->nombre ?? 'sin plan' }}
                        · creada {{ $l->created_at->format('d/m/Y') }}
                        @if($l->expira_en)
                            · vence {{ $l->expira_en->format('d/m/Y') }}
                        @endif
                    </p>

                    {{-- Recortada: entera ensuciaba la fila y aqui no se puede
                         copiar. Va completa en el detalle, con su boton. --}}
                    <p class="mt-1 font-mono text-xs text-gray-400">{{ Str::limit($l->clave, 30) }}</p>
                </div>

                <div class="w-40 shrink-0">
                    <p class="text-sm font-medium text-gray-900">
                        {{ number_format($l->usadas_mes) }} <span class="text-gray-400">/ {{ number_format($tope) }}</span>
                    </p>
                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-amber-500' : 'bg-green-500') }}"
                             style="width: {{ $pct }}%"></div>
                    </div>
                </div>

                {{-- Los mismos botones que el resto del sistema: x-icon-action
                     es el componente que usan Empresas, Documentos y API. Con
                     botones a mano esta pantalla se veia de otro sitio. --}}
                <div class="flex shrink-0 items-center gap-1.5">
                    <x-icon-action icon="ver" label="Ver esta API Key" color="blue" type="button"
                                   @click="detalle = {{ Illuminate\Support\Js::from([
                                       'nombre' => $l->nombre,
                                       'estado' => $estado,
                                       'entorno' => $l->entorno,
                                       'clave' => $l->clave,
                                       'pista' => $l->secreto_pista,
                                       'titular' => $l->nombreDelTitular(),
                                       'plan' => $l->plan?->nombre,
                                       'servicios' => collect($l->servicios)->map(fn ($x) => strtoupper($x))->join(' y '),
                                       'usadas' => $l->usadas_mes,
                                       'tope' => $tope,
                                       'creada' => $l->created_at->format('d/m/Y'),
                                       'expira' => $l->expira_en?->format('d/m/Y'),
                                       'ultimo_uso' => $l->ultimo_uso_en?->format('d/m/Y H:i'),
                                   ]) }}" />

                    <x-icon-action icon="editar" label="Editar" color="slate" type="button"
                                   @click="llave = {{ Illuminate\Support\Js::from([
                                       'id' => $l->id,
                                       'nombre' => $l->nombre,
                                       'titular_tipo' => $l->company_id ? 'empresa' : 'externo',
                                       'company_id' => $l->company_id,
                                       'titular' => $l->titular,
                                       'titular_documento' => $l->titular_documento,
                                       'titular_email' => $l->titular_email,
                                       'api_plan_id' => $l->api_plan_id,
                                       'entorno' => $l->entorno,
                                       'servicios' => (array) $l->servicios,
                                       'expira_en' => $l->expira_en?->toDateString(),
                                   ]) }}; nueva = false" />

                    <form method="POST" action="{{ route('super-admin.consultas.llaves.alternar', $l) }}">
                        @csrf
                        <x-icon-action :icon="$l->activa ? 'bloquear' : 'desbloquear'"
                                       :label="$l->activa ? 'Bloquear' : 'Desbloquear'"
                                       :color="$l->activa ? 'amber' : 'emerald'" />
                    </form>

                    <form method="POST" action="{{ route('super-admin.consultas.llaves.borrar', $l) }}"
                          onsubmit="return confirm('Se elimina «{{ $l->nombre }}» y todo su historial de consultas.

Quien la use dejará de tener acceso al instante, y no quedará constancia de lo que consumió.

¿Continuar?')">
                        @csrf
                        @method('DELETE')
                        <x-icon-action icon="eliminar" label="Eliminar" color="red" />
                    </form>
                </div>
            </div>
        @empty
            <div class="px-5 py-12 text-center">
                <p class="text-sm text-gray-500">Todavía no hay ninguna API Key.</p>
                <p class="mt-1 text-xs text-gray-400">Crea una para que alguien pueda usar las consultas.</p>
            </div>
        @endforelse
    </section>

    {{-- Crear y editar en modal: son siete campos y meterlos en la fila la
         volveria ilegible. --}}
    <div x-show="llave || nueva" x-cloak
         @keydown.escape.window="llave = null; nueva = false"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
        <div @click.outside="llave = null; nueva = false"
             class="my-auto w-full max-w-lg overflow-hidden rounded-lg bg-white shadow-xl">

            <form method="POST"
                  :action="nueva
                      ? '{{ route('super-admin.consultas.llaves.guardar') }}'
                      : '{{ url('super-admin/consultas/llaves') }}/' + (llave?.id ?? '')"
                  x-data="{
                      marcados: @js($apis->pluck('slug')),
                      alterna(slug, on) {
                          this.marcados = on
                              ? [...new Set([...this.marcados, slug])]
                              : this.marcados.filter(s => s !== slug);
                      },

                      buscando: false,
                      aviso: '',
                      avisoTipo: '',

                      /* El nombre sale del documento, como en el alta de un
                         cliente: se teclea una vez y no dos, y la razon social
                         queda tal cual figura, sin erratas. */
                      async buscar(numero) {
                          const n = String(numero || '').replace(/\D/g, '');
                          const tipo = n.length === 11 ? 'ruc' : (n.length === 8 ? 'dni' : null);

                          this.aviso = '';
                          this.avisoTipo = '';

                          if (! tipo) return;

                          this.buscando = true;
                          try {
                              const res = await fetch('{{ url('super-admin/consultas/documento') }}/' + tipo + '/' + n, {
                                  headers: { 'Accept': 'application/json' },
                              });
                              const d = await res.json();

                              if (! d.encontrado) {
                                  this.aviso = d.mensaje;
                                  this.avisoTipo = 'error';
                                  return;
                              }

                              this.$refs.titular.value = d.nombre;

                              /* Un RUC de baja o no habido se puede dar de alta
                                 igual, pero conviene saberlo antes de cobrarle. */
                              const ojo = [d.estado, d.condicion].filter(v => v && v !== 'ACTIVO' && v !== 'HABIDO');
                              this.aviso = ojo.length ? 'Figura como ' + ojo.join(' y ') + '.' : d.nombre;
                              this.avisoTipo = ojo.length ? 'ojo' : 'ok';
                          } catch (e) {
                              this.aviso = 'No se pudo consultar. Escríbelo a mano.';
                              this.avisoTipo = 'error';
                          } finally {
                              this.buscando = false;
                          }
                      },
                  }"
                  x-effect="if (llave) { marcados = llave.servicios ?? []; }">
                @csrf
                <template x-if="!nueva"><input type="hidden" name="_method" value="PUT"></template>

                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900" x-text="nueva ? 'Nueva llave' : 'Editar llave'"></h3>
                    <p class="mt-0.5 text-xs text-gray-500">Consulta datos reales con el plan que le pongas.</p>
                </div>

                {{-- Cuatro campos: a quien, a que le da acceso, con que plan y
                     hasta cuando.

                     Sin entorno: esta pestaña crea de produccion y las de
                     prueba se crean en Sandbox, que tiene su propio formulario.
                     Teniendolo aqui se podian crear sandbox desde dos sitios y
                     equivocarse de entorno sin enterarse.

                     Sin desplegable de empresas: las consultas se venden aparte
                     de la facturacion, asi que quien compra no tiene por que
                     estar dado de alta como empresa.

                     El nombre no se pregunta: se arma con el titular. --}}
                <div class="space-y-4 px-5 py-4">

                    <input type="hidden" name="entorno" value="produccion">
                    <input type="hidden" name="titular_tipo" value="externo">

                    {{-- El documento primero: de el sale el nombre. Ademas es
                         el dato que hace falta para facturarle. --}}
                    <div>
                        <label for="l_doc" class="mb-1.5 block text-sm font-medium text-gray-900">
                            RUC o DNI
                        </label>
                        <input type="text" name="titular_documento" id="l_doc" maxlength="11"
                               :value="llave?.titular_documento ?? ''"
                               @input.debounce.400ms="buscar($event.target.value)"
                               placeholder="11 dígitos para RUC, 8 para DNI"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">

                        <template x-if="aviso">
                            <p class="mt-1.5 text-xs"
                               :class="{ 'text-green-700': avisoTipo === 'ok', 'text-amber-700': avisoTipo === 'ojo', 'text-red-600': avisoTipo === 'error' }"
                               x-text="aviso"></p>
                        </template>
                        <p x-show="buscando" class="mt-1.5 text-xs text-gray-500">Consultando…</p>
                    </div>

                    <div>
                        <label for="l_titular" class="mb-1.5 block text-sm font-medium text-gray-900">
                            Nombre o razón social
                        </label>
                        <input type="text" name="titular" id="l_titular" maxlength="120" required
                               x-ref="titular" :value="llave?.titular ?? ''"
                               placeholder="Se rellena al escribir el documento"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase placeholder:normal-case outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <p class="mb-1.5 block text-sm font-medium text-gray-900">¿A qué le da acceso?</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach($apis as $api)
                                <label class="flex cursor-pointer gap-2.5 rounded-lg border p-3 transition"
                                       :class="marcados.includes('{{ $api->slug }}') ? 'border-blue-500 bg-blue-50/50' : 'border-gray-200 hover:bg-gray-50'">
                                    <input type="checkbox" name="servicios[]" value="{{ $api->slug }}"
                                           :checked="marcados.includes('{{ $api->slug }}')"
                                           @change="alterna('{{ $api->slug }}', $event.target.checked)"
                                           class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-gray-900">{{ $api->nombre }}</span>
                                        @if($api->descripcion)
                                            <span class="mt-0.5 block text-xs leading-snug text-gray-500">{{ $api->descripcion }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- El plan si se pregunta, y es lo que separa esta pestaña
                         de Sandbox: aqui se cobra, y de el salen las cuotas. --}}
                    <div>
                        <label for="l_plan" class="mb-1.5 block text-sm font-medium text-gray-900">Plan contratado</label>
                        <select name="api_plan_id" id="l_plan" required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($planesApi as $p)
                                <option value="{{ $p->id }}" :selected="llave?.api_plan_id == {{ $p->id }}">
                                    {{ $p->nombre }}@if($p->a_medida) — a convenir @elseif((float) $p->precio_mensual > 0) — S/ {{ number_format($p->precio_mensual, 2) }}/mes @else — sin costo @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="l_expira" class="mb-1.5 block text-sm font-medium text-gray-900">
                            Vence el <span class="font-normal text-gray-400">— opcional</span>
                        </label>
                        <input type="date" name="expira_en" id="l_expira"
                               :value="llave?.expira_en ?? ''"
                               min="{{ now()->addDay()->format('Y-m-d') }}"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Vacío = sin caducidad. Al llegar el día deja de funcionar.</p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-4">
                    <button type="button" @click="llave = null; nueva = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Detalle de una API Key. La clave se copia de aqui; el secreto no esta,
         y se dice por que: solo lo tiene el cliente. --}}
    <div x-show="detalle" x-cloak
         @keydown.escape.window="detalle = null"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
        <div @click.outside="detalle = null"
             class="my-auto w-full max-w-lg overflow-hidden rounded-lg bg-white shadow-xl">

            <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-4">
                <div class="min-w-0">
                    <h3 class="truncate text-base font-semibold text-gray-900" x-text="detalle?.nombre"></h3>
                    <p class="mt-0.5 truncate text-xs text-gray-500">
                        <span x-text="detalle?.titular"></span> ·
                        <span x-text="detalle?.servicios"></span> ·
                        <span x-text="detalle?.plan ?? 'sin plan'"></span>
                    </p>
                </div>
                <span class="ml-auto inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                      :class="{
                          'bg-emerald-50 text-emerald-700': detalle?.estado === 'activa',
                          'bg-blue-50 text-blue-700': detalle?.estado === 'sandbox',
                          'bg-amber-50 text-amber-700': detalle?.estado === 'vencida',
                          'bg-red-50 text-red-700': detalle?.estado === 'bloqueada',
                      }">
                    <span class="h-1.5 w-1.5 rounded-full"
                          :class="{
                              'bg-emerald-500': detalle?.estado === 'activa',
                              'bg-blue-500': detalle?.estado === 'sandbox',
                              'bg-amber-500': detalle?.estado === 'vencida',
                              'bg-red-500': detalle?.estado === 'bloqueada',
                          }"></span>
                    <span x-text="({ activa: 'Activa', sandbox: 'Sandbox', vencida: 'Vencida', bloqueada: 'Bloqueada' })[detalle?.estado]"></span>
                </span>

                <button type="button" @click="detalle = null" aria-label="Cerrar"
                        class="shrink-0 rounded-md p-2 text-gray-500 hover:bg-gray-100">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Con la misma forma que el modal de credenciales del modulo API:
                 el panel indigo con lo que se le entrega al programador, y
                 debajo los tres datos de apoyo en una franja. --}}
            <div class="p-4">
                <div class="overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/40">
                    <div class="flex items-center justify-between border-b border-indigo-200 bg-indigo-50 px-4 py-2.5">
                        <span class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Credenciales</span>
                        <span class="rounded px-2 py-0.5 text-xs font-medium"
                              :class="detalle?.entorno === 'sandbox' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                              x-text="detalle?.entorno === 'sandbox' ? 'Sandbox' : 'Producción'"></span>
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
                        <dt class="text-xs text-gray-500">Este mes</dt>
                        <dd class="mt-0.5 truncate text-sm font-semibold text-gray-900">
                            <span x-text="(detalle?.usadas ?? 0).toLocaleString('es-PE')"></span>
                            <span class="font-normal text-gray-400">de</span>
                            <span x-text="(detalle?.tope ?? 0).toLocaleString('es-PE')"></span>
                        </dd>
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
                        <dt class="text-xs text-gray-500">Vence</dt>
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
