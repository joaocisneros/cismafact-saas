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
            <div class="my-auto w-full max-w-2xl overflow-hidden rounded-lg bg-white shadow-xl">
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
                            @foreach(['URL base' => url('/api'), 'X-Api-Key' => $nueva['clave'], 'X-Api-Secret' => $nueva['secreto']] as $etiqueta => $valor)
                                <div class="flex items-center gap-3 px-4 py-2.5">
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

    {{-- En tabla, como Sandbox.

         En lista cada dato colgaba de donde cabia: el titular, el plan y las
         fechas iban seguidos en una linea de puntos que hay que leer entera
         para encontrar uno, y el consumo flotaba a la derecha sin decir de
         que era. Con columnas se compara de arriba abajo y se busca por la
         cabecera. --}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        @if($llaves->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="text-sm text-gray-500">Todavía no hay ninguna API Key.</p>
                <p class="mt-1 text-xs text-gray-400">Crea una para que alguien pueda usar las consultas.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full table-fixed divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="w-1/3 px-5 py-3">Titular</th>
                            <th class="w-1/12 px-5 py-3">Plan</th>
                            <th class="w-1/6 px-5 py-3">Consumo del mes</th>
                            <th class="w-1/6 px-5 py-3">Vigencia</th>
                            <th class="w-1/4 px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($llaves as $l)
                            @php
                                $estado = $l->estado();
                                $tope = $l->plan
                                    ? $apis->whereIn('slug', (array) $l->servicios)->sum(fn ($a) => $a->limiteDelPlan($l->api_plan_id))
                                    : 0;
                                $pct = $tope > 0 ? min(100, round($l->usadas_mes / $tope * 100)) : 0;
                            @endphp

                            <tr class="{{ $estado === 'activa' ? '' : 'bg-gray-50/60' }}">
                                {{-- De quien es manda sobre como se llama la
                                     llave: para buscar se busca por el cliente.
                                     Debajo el nombre y la clave recortada, que
                                     son los que distinguen cuando uno tiene
                                     varias. Entera va en el detalle, que ahi
                                     hay boton para copiarla. --}}
                                <td class="px-5 py-3">
                                    <p class="truncate font-medium text-gray-900" title="{{ $l->nombreDelTitular() }}">
                                        {{ $l->nombreDelTitular() }}
                                    </p>
                                    <p class="truncate text-xs text-gray-500" title="{{ $l->nombre }}">
                                        {{ $l->entorno === 'sandbox' ? 'Sandbox' : 'Producción' }}
                                        <span class="font-mono text-gray-400">· {{ Str::limit($l->clave, 22) }}</span>
                                    </p>
                                </td>


                                <td class="px-5 py-3">
                                    @if($l->plan)
                                        <span class="text-gray-700">{{ $l->plan->nombre }}</span>
                                    @else
                                        <span class="text-xs text-red-600" title="No tiene cuota: sus consultas se rechazan">sin plan</span>
                                    @endif
                                </td>

                                {{-- Una linea por servicio, que es como se gasta.

                                     Sumados enseñaban una cuenta que no existe:
                                     con 1000 de RUC agotados y 300 de DNI sin
                                     tocar salia «1000 / 1300, 77%» en ambar,
                                     tranquilizador, mientras las consultas de RUC
                                     ya devolvian 429. Y nunca llegaba al rojo,
                                     porque lo que sobraba del DNI tapaba lo
                                     agotado del RUC. --}}
                                <td class="px-5 py-3">
                                    <div class="space-y-1.5">
                                        @foreach($apis->whereIn('slug', (array) $l->servicios) as $api)
                                            @php
                                                $usadasApi = (int) ($consumoPorApi[$l->id][$api->id] ?? 0);
                                                $topeApi = $l->topeDe($api);
                                                $pctApi = $topeApi > 0 ? min(100, round($usadasApi / $topeApi * 100)) : 0;
                                            @endphp
                                            <div>
                                                <p class="flex items-baseline justify-between gap-2 text-xs">
                                                    <span class="font-medium uppercase text-gray-500">{{ $api->slug }}</span>
                                                    <span>
                                                        <span class="font-medium text-gray-900">{{ number_format($usadasApi) }}</span>
                                                        <span class="text-gray-400">/ {{ number_format($topeApi) }}</span>
                                                    </span>
                                                </p>
                                                <div class="mt-0.5 h-1 w-full overflow-hidden rounded-full bg-gray-100">
                                                    <div class="h-full rounded-full {{ $pctApi >= 100 ? 'bg-red-500' : ($pctApi >= 80 ? 'bg-amber-500' : 'bg-green-500') }}"
                                                         style="width: {{ $pctApi }}%"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                {{-- Estado y caducidad juntos: lo que se
                                     pregunta de una llave es si hoy funciona y
                                     hasta cuando va a seguir haciendolo. --}}
                                <td class="px-5 py-3">
                                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium
                                        @if($estado === 'activa') bg-green-50 text-green-700
                                        @elseif($estado === 'sandbox') bg-blue-50 text-blue-700
                                        @elseif($estado === 'vencida') bg-amber-50 text-amber-700
                                        @else bg-red-50 text-red-700 @endif">
                                        {{ ['activa' => '● Activa', 'sandbox' => 'Sandbox', 'vencida' => 'Vencida', 'bloqueada' => 'Bloqueada'][$estado] }}
                                    </span>
                                    <p class="mt-1 text-xs text-gray-500">
                                        @if($l->expira_en)
                                            hasta el {{ $l->expira_en->format('d/m/Y') }}
                                        @else
                                            sin caducidad
                                        @endif
                                    </p>
                                </td>

                                <td class="px-5 py-3">
                                    {{-- Los mismos botones que el resto del sistema: x-icon-action
                                         es el componente que usan Empresas, Documentos y API. Con
                                         botones a mano esta pantalla se veia de otro sitio. --}}
                                    <div class="flex shrink-0 items-center gap-1.5">
                                        <x-icon-action icon="ver" label="Ver esta API Key" color="blue" type="button"
                                                       @click="detalle = {{ Illuminate\Support\Js::from([
                                                           'id' => $l->id,
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

                                        {{-- Lo tenia Sandbox y aqui no, que es al reves de lo que
                                             hace falta: al que paga no se le puede decir que se
                                             borra su llave y empiece de cero porque perdio el
                                             secreto. --}}
                                        <form method="POST" action="{{ route('super-admin.consultas.llaves.regenerar', $l) }}"
                                              onsubmit="return confirm('Se genera un secreto nuevo para «{{ $l->nombre }}».

                    El actual deja de funcionar en cuanto se guarde, así que hay que pasarle el nuevo al cliente. La clave (X-Api-Key) no cambia.

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
                                              onsubmit="return confirm('Se elimina «{{ $l->nombre }}» y todo su historial de consultas.

                    Quien la use dejará de tener acceso al instante, y no quedará constancia de lo que consumió.

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
    </section>

    {{-- Crear y editar en modal: son siete campos y meterlos en la fila la
         volveria ilegible. --}}
    {{-- El modal solo se oculta, no se destruye: al cerrarlo y volver a abrir,
         los campos conservaban lo ultimo que se escribio. Se limpian al salir,
         para que «Nueva llave» empiece siempre en blanco. --}}
    <div x-show="llave || nueva" x-cloak
         @keydown.escape.window="llave = null; nueva = false"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
        <div @click.outside="llave = null; nueva = false"
             class="my-auto w-full max-w-xl overflow-hidden rounded-lg bg-white shadow-xl">

            <form method="POST" x-ref="formulario"
                  :action="nueva
                      ? '{{ route('super-admin.consultas.llaves.guardar') }}'
                      : '{{ url('super-admin/consultas/llaves') }}/' + (llave?.id ?? '')"
                  x-data="{
                      marcados: [],
                      alterna(slug, on) {
                          this.marcados = on
                              ? [...new Set([...this.marcados, slug])]
                              : this.marcados.filter(s => s !== slug);
                      },

                      buscando: false,
                      aviso: '',
                      avisoTipo: '',

                      limpiar() {
                          this.aviso = '';
                          this.avisoTipo = '';
                          this.buscando = false;
                          this.marcados = [];
                          this.$refs.formulario?.reset();
                      },

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
                  x-effect="
                      if (llave) {
                          marcados = llave.servicios ?? [];
                      } else if (! nueva) {
                          /* El modal se oculta pero no se destruye, asi que sus
                             campos guardaban lo ultimo escrito: al abrir «Nueva
                             llave» despues de haber editado una, salian sus
                             datos. Se vacia al cerrarse. */
                          limpiar();
                      }
                  ">
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
                <div class="space-y-3 px-5 py-4">

                    <input type="hidden" name="entorno" value="produccion">
                    <input type="hidden" name="titular_tipo" value="externo">

                    {{-- El documento primero: de el sale el nombre. Ademas es
                         el dato que hace falta para facturarle. --}}
                    {{-- En la misma fila porque uno sale del otro, pero no a
                         medias: el documento son once cifras y el nombre puede
                         tener noventa, asi que se lleva el doble de sitio. --}}
                    <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label for="l_doc" class="mb-1 block text-sm font-medium text-gray-900">
                            RUC o DNI
                        </label>
                        <input type="text" name="titular_documento" id="l_doc" maxlength="11"
                               :value="llave?.titular_documento ?? ''"
                               @input.debounce.400ms="buscar($event.target.value)"
                               placeholder="20601030013"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">11 dígitos si es RUC, 8 si es DNI.</p>

                        {{-- Solo cuando hay algo que decir: el nombre ya se ve
                             en su campo, repetirlo debajo en verde sobraba. --}}
                        <template x-if="aviso && avisoTipo !== 'ok'">
                            <p class="mt-1.5 text-xs"
                               :class="{ 'text-amber-700': avisoTipo === 'ojo', 'text-red-600': avisoTipo === 'error' }"
                               x-text="aviso"></p>
                        </template>
                        <p x-show="buscando" class="mt-1.5 text-xs text-gray-500">Consultando…</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="l_titular" class="mb-1 block text-sm font-medium text-gray-900">
                            Nombre o razón social
                        </label>
                        <input type="text" name="titular" id="l_titular" maxlength="120" required
                               x-ref="titular" :value="llave?.titular ?? ''"
                               placeholder="Se completa al escribir el documento"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase placeholder:normal-case outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    </div>

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

                    {{-- El plan si se pregunta, y es lo que separa esta pestaña
                         de Sandbox: aqui se cobra, y de el salen las cuotas. --}}
                    <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="l_plan" class="mb-1 block text-sm font-medium text-gray-900">Plan contratado</label>
                        {{-- Sin los planes gratis: aqui se da de alta a quien
                             paga, y lo gratuito se reparte desde Sandbox. Salia
                             el primero de la lista, asi que un despiste al elegir
                             regalaba trescientas consultas al mes.

                             Los de a convenir si salen: cuestan cero en la ficha
                             porque el precio se acuerda aparte, no porque sean
                             gratis. --}}
                        <select name="api_plan_id" id="l_plan" required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($planesApi->filter(fn ($p) => $p->a_medida || (float) $p->precio_mensual > 0) as $p)
                                {{-- Las cuotas y no el precio.

                                     Esta pantalla se abre delante de clientes y
                                     el importe no pinta nada en ella. Ademas al
                                     elegir plan lo que se compara es cuanto da
                                     cada uno; la tarifa esta en Planes, que es
                                     donde se mira cuando toca cobrar. --}}
                                <option value="{{ $p->id }}" :selected="llave?.api_plan_id == {{ $p->id }}">
                                    {{ $p->nombre }} — {{ $apis->map(fn ($a) => number_format($a->limiteDelPlan($p->id)) . ' ' . strtoupper($a->slug))->join(' · ') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Se piensa en cuanto dura el contrato, no en el dia del
                         calendario en que cae: los botones lo calculan y el
                         campo se queda para una fecha concreta. --}}
                    <div x-data="{
                             enMeses(n) {
                                 const d = new Date();
                                 d.setMonth(d.getMonth() + n);
                                 this.$refs.expira.value = d.toISOString().slice(0, 10);
                             },
                         }">
                        <label for="l_expira" class="mb-1 block text-sm font-medium text-gray-900">
                            Vence el <span class="font-normal text-gray-400">— opcional</span>
                        </label>

                        <div class="mb-1.5 flex flex-wrap gap-1.5">
                            @foreach([1 => '1 mes', 6 => '6 meses', 12 => '1 año'] as $n => $etiqueta)
                                <button type="button" @click="enMeses({{ $n }})"
                                        class="rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                                    {{ $etiqueta }}
                                </button>
                            @endforeach
                            <button type="button" @click="$refs.expira.value = ''"
                                    class="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs text-gray-500 transition hover:bg-gray-50">
                                Sin caducidad
                            </button>
                        </div>

                        <input type="date" name="expira_en" id="l_expira" x-ref="expira"
                               :value="llave?.expira_en ?? ''"
                               min="{{ now()->addDay()->format('Y-m-d') }}"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
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
    {{-- La ficha de la llave, con el mismo diseño y el mismo ancho que las de
         API Facturación y Sandbox Facturación: son lo mismo —una credencial
         que se entrega— y hasta ahora cada una lo colocaba a su manera. --}}
    <div x-show="detalle" x-cloak
         @keydown.escape.window="detalle = null"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
        <div @click.outside="detalle = null" class="my-auto w-full max-w-4xl">
            <x-ficha-credencial ancho="w-full">
                <x-slot:titulo><span x-text="detalle?.nombre"></span></x-slot:titulo>

                <x-slot:subtitulo>
                    <span x-text="detalle?.titular"></span> ·
                    <span x-text="detalle?.servicios"></span> ·
                    <span x-text="detalle?.plan ?? 'sin plan'"></span>
                </x-slot:subtitulo>

                <x-slot:estado>
                    <div class="flex items-center gap-2">
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
                            <span x-text="{ activa: 'Activa', sandbox: 'Sandbox', vencida: 'Vencida', bloqueada: 'Bloqueada' }[detalle?.estado] ?? ''"></span>
                        </span>

                        <button type="button" @click="detalle = null" aria-label="Cerrar"
                                class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </x-slot:estado>

                <x-slot:etiqueta>
                    <span class="rounded px-2 py-0.5 text-xs font-medium"
                          :class="detalle?.entorno === 'sandbox' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                          x-text="detalle?.entorno === 'sandbox' ? 'Sandbox' : 'Producción'"></span>
                </x-slot:etiqueta>

                <x-slot:credenciales>
                    <x-fila-credencial etiqueta="URL base">
                        {{ url('/api') }}
                        <x-slot:boton>
                            <button type="button" onclick="window.copyCompanyCredential(this, @js(url('/api')))"
                                    class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                        </x-slot:boton>
                    </x-fila-credencial>

                    <x-fila-credencial etiqueta="X-Api-Key">
                        <span x-text="detalle?.clave"></span>
                        <x-slot:boton>
                            <button type="button" @click="window.copyCompanyCredential($el, detalle?.clave)"
                                    class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                        </x-slot:boton>
                    </x-fila-credencial>

                    {{-- Se pide al abrir la ficha y no al pulsar «Mostrar»: así la
                         espera se va con el rato que se pasa leyendo la clave, y
                         con el listado no viaja, que ahí saldría el de todas. --}}
                    <div x-data="{ visible: false, valor: null, cargando: false, id: null,
                             async precargar(id) {
                                 if (! id) return;
                                 this.cargando = true;
                                 try {
                                     const r = await fetch('{{ url('super-admin/consultas/llaves') }}/' + id + '/secreto', {
                                         headers: { 'Accept': 'application/json' },
                                     });
                                     this.valor = (await r.json()).secreto;
                                 } catch (e) {
                                     this.valor = null;
                                 } finally {
                                     this.cargando = false;
                                 }
                             },
                             async mostrar() {
                                 if (this.visible) { this.visible = false; return; }
                                 if (! this.valor) await this.precargar(this.id);
                                 this.visible = !! this.valor;
                             },
                         }"
                         x-effect="visible = false; valor = null; id = detalle?.id; precargar(detalle?.id)">
                        <x-fila-credencial etiqueta="X-Api-Secret">
                            <span x-show="! visible" class="text-gray-400">··················<span x-text="detalle?.pista"></span></span>
                            <span x-show="visible" x-text="valor"></span>
                            <x-slot:boton>
                                <button type="button" @click="mostrar()" :disabled="cargando"
                                        class="shrink-0 rounded-md border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50"
                                        x-text="cargando ? '…' : (visible ? 'Ocultar' : 'Mostrar')"></button>

                                <button type="button" x-show="visible" x-cloak
                                        @click="window.copyCompanyCredential($el, valor)"
                                        class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                            </x-slot:boton>
                        </x-fila-credencial>
                    </div>
                </x-slot:credenciales>

                <x-slot:nota>
                    No sale con la página: se pide solo al abrir esta ficha.
                </x-slot:nota>

                <x-slot:metricas>
                    <x-metrica-credencial titulo="Este mes">
                        <span x-text="(detalle?.usadas ?? 0).toLocaleString('es-PE')"></span>
                        <span class="font-normal text-gray-400">de</span>
                        <span x-text="(detalle?.tope ?? 0).toLocaleString('es-PE')"></span>
                    </x-metrica-credencial>
                    <x-metrica-credencial titulo="Último uso">
                        <span x-text="detalle?.ultimo_uso ?? 'Nunca'"></span>
                    </x-metrica-credencial>
                    <x-metrica-credencial titulo="Creada">
                        <span x-text="detalle?.creada"></span>
                    </x-metrica-credencial>
                    <x-metrica-credencial titulo="Vence">
                        <span x-text="detalle?.expira ?? 'No caduca'"></span>
                    </x-metrica-credencial>
                </x-slot:metricas>

                <x-slot:acciones>
                    <button type="button" @click="detalle = null"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Cerrar
                    </button>
                </x-slot:acciones>
            </x-ficha-credencial>
        </div>
    </div>

</div>
