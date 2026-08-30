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
                                <div class="flex items-center gap-3 px-4 py-3">
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
                          onsubmit="return confirm('Se elimina «{{ $l->nombre }}». Quien la use dejará de tener acceso al instante. ¿Continuar?')">
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
                  x-data="{ tipo: 'empresa' }"
                  x-effect="tipo = llave?.titular_tipo ?? 'empresa'">
                @csrf
                <template x-if="!nueva"><input type="hidden" name="_method" value="PUT"></template>

                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900" x-text="nueva ? 'Nueva API Key' : 'Editar llave'"></h3>
                </div>

                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label for="l_nombre" class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
                        <input type="text" name="nombre" id="l_nombre" required maxlength="80"
                               :value="llave?.nombre ?? ''"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Producción - Web">
                        <p class="mt-1 text-xs text-gray-500">Para saber cuál es cuál cuando haya varias.</p>
                    </div>

                    <div>
                        <p class="mb-1 block text-sm font-medium text-gray-700">Para quién</p>
                        <div class="flex gap-4">
                            @foreach(['empresa' => 'Una empresa del sistema', 'externo' => 'Alguien de fuera'] as $valor => $texto)
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="radio" name="titular_tipo" value="{{ $valor }}" x-model="tipo"
                                           class="border-gray-300 text-blue-600 focus:ring-blue-500">
                                    {{ $texto }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div x-show="tipo === 'empresa'">
                        <label for="l_empresa" class="mb-1 block text-sm font-medium text-gray-700">Empresa</label>
                        <select name="company_id" id="l_empresa"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Elige…</option>
                            @foreach($empresas as $e)
                                <option value="{{ $e->id }}" :selected="llave?.company_id == {{ $e->id }}">
                                    {{ $e->razon_social }} — {{ $e->ruc }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="tipo === 'externo'" x-cloak class="space-y-3">
                        <div>
                            <label for="l_titular" class="mb-1 block text-sm font-medium text-gray-700">Nombre o razón social</label>
                            <input type="text" name="titular" id="l_titular" maxlength="120"
                                   :value="llave?.titular ?? ''"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="l_doc" class="mb-1 block text-sm font-medium text-gray-700">RUC o DNI</label>
                                <input type="text" name="titular_documento" id="l_doc" maxlength="20"
                                       :value="llave?.titular_documento ?? ''"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="l_email" class="mb-1 block text-sm font-medium text-gray-700">Correo</label>
                                <input type="email" name="titular_email" id="l_email" maxlength="120"
                                       :value="llave?.titular_email ?? ''"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <p class="mb-1 block text-sm font-medium text-gray-700">A qué da acceso</p>
                        <div class="flex flex-wrap gap-4">
                            @foreach($apis as $api)
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="servicios[]" value="{{ $api->slug }}"
                                           :checked="(llave?.servicios ?? ['{{ $apis->first()->slug }}']).includes('{{ $api->slug }}')"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    {{ $api->nombre }}
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Si le roban esta API Key, solo podrán usar lo que esté marcado.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="l_plan" class="mb-1 block text-sm font-medium text-gray-700">Plan</label>
                            <select name="api_plan_id" id="l_plan" required
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                                @foreach($planesApi as $p)
                                    <option value="{{ $p->id }}" :selected="llave?.api_plan_id == {{ $p->id }}">
                                        {{ $p->nombre }}{{ $p->a_medida ? '' : ' — S/ ' . rtrim(rtrim(number_format((float) $p->precio_mensual, 2), '0'), '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="l_entorno" class="mb-1 block text-sm font-medium text-gray-700">Entorno</label>
                            <select name="entorno" id="l_entorno" required
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="produccion" :selected="(llave?.entorno ?? 'produccion') === 'produccion'">Producción</option>
                                <option value="sandbox" :selected="llave?.entorno === 'sandbox'">Sandbox — datos de ejemplo</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="l_expira" class="mb-1 block text-sm font-medium text-gray-700">
                            Vence el <span class="font-normal text-gray-400">— opcional</span>
                        </label>
                        <input type="date" name="expira_en" id="l_expira" :value="llave?.expira_en ?? ''"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">
                            Vacío para que no caduque. Con fecha, deja de responder sola.
                        </p>
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
            <div class="p-5">
                <div class="flex justify-end">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
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
                </div>

                <div class="mt-3 overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/40">
                    <div class="flex items-center justify-between border-b border-indigo-200 bg-indigo-50 px-4 py-2.5">
                        <span class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Credenciales</span>
                        <span class="rounded px-2 py-0.5 text-xs font-medium"
                              :class="detalle?.entorno === 'sandbox' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                              x-text="detalle?.entorno === 'sandbox' ? 'Sandbox' : 'Producción'"></span>
                    </div>

                    <div class="divide-y divide-indigo-100">
                        <div class="flex items-center gap-3 px-4 py-3">
                            <span class="w-24 shrink-0 text-xs font-medium text-indigo-900/70">URL base</span>
                            <code class="min-w-0 flex-1 break-all rounded bg-white px-2 py-1.5 font-mono text-xs text-gray-800 ring-1 ring-indigo-100">{{ url('/api/consultas') }}</code>
                            <button type="button" onclick="window.copyCompanyCredential(this, @js(url('/api/consultas')))"
                                    class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                        </div>

                        <div class="flex items-center gap-3 px-4 py-3">
                            <span class="w-24 shrink-0 text-xs font-medium text-indigo-900/70">X-Api-Key</span>
                            <code class="min-w-0 flex-1 break-all rounded bg-white px-2 py-1.5 font-mono text-xs text-gray-800 ring-1 ring-indigo-100" x-text="detalle?.clave"></code>
                            <button type="button" @click="window.copyCompanyCredential($el, detalle?.clave)"
                                    class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                        </div>

                        <div class="flex items-center gap-3 px-4 py-3">
                            <span class="w-24 shrink-0 text-xs font-medium text-indigo-900/70">X-Api-Secret</span>
                            <code class="min-w-0 flex-1 rounded bg-white px-2 py-1.5 font-mono text-xs text-gray-400 ring-1 ring-indigo-100">
                                ··················<span x-text="detalle?.pista"></span>
                            </code>
                        </div>

                        <div class="px-4 py-2.5 text-xs text-gray-500">
                            El secreto solo lo tiene el cliente. Si lo perdió, hay que crearle otra API Key.
                        </div>
                    </div>
                </div>

                <dl class="mt-5 grid grid-cols-3 gap-px overflow-hidden rounded-lg bg-gray-200 text-center">
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
                </dl>

                <p class="mt-3 text-center text-xs text-gray-500">
                    Vence: <span class="font-medium text-gray-700" x-text="detalle?.expira ?? 'no caduca'"></span>
                </p>
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
