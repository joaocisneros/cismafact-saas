{{-- Las llaves con las que se entra a las consultas.
     Aparte de las de emision a proposito: quien compra consultas puede no
     facturar, y bloquearle una cosa no debe cortarle la otra. Un mismo titular
     quiere varias —una por sistema suyo— para poder cortar una sin dejar las
     demas sin servicio. --}}
<div x-data="{ llave: null, nueva: false }">

    @if($nueva = session('llave_creada'))
        {{-- El secreto se enseña UNA vez. Despues queda cifrado y no se vuelve
             a mostrar: si se pierde, se genera otra llave. --}}
        <div class="mb-5 rounded-xl border-2 border-green-300 bg-green-50 p-5">
            <p class="text-sm font-semibold text-green-900">«{{ $nueva['nombre'] }}» creada</p>
            <p class="mt-1 text-xs text-green-800">
                Cópiala ahora: <strong>el secreto no se vuelve a mostrar.</strong> Si se pierde, se genera otra llave.
            </p>

            <div class="mt-3 space-y-2">
                @foreach(['Clave' => $nueva['clave'], 'Secreto' => $nueva['secreto']] as $etiqueta => $valor)
                    <div>
                        <p class="text-xs font-medium text-green-900">{{ $etiqueta }}</p>
                        <div class="mt-0.5 flex items-center gap-2">
                            <code class="flex-1 truncate rounded-lg border border-green-200 bg-white px-3 py-2 font-mono text-xs text-gray-800">{{ $valor }}</code>
                            <button type="button" onclick="window.copyCompanyCredential(this, @js($valor))"
                                    class="shrink-0 rounded-lg border border-green-300 bg-white px-3 py-2 text-xs font-medium text-green-800 transition hover:bg-green-100">
                                Copiar
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Llaves de acceso</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Las de las consultas, no las de emitir. Bloquear una no afecta a la facturación.
            </p>
        </div>
        <button type="button" @click="nueva = true; llave = null"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
            Nueva llave
        </button>
    </div>

    <section class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        @forelse($llaves as $l)
            @php
                $estado = $l->estado();
                $tope = $l->plan
                    ? $apis->whereIn('slug', (array) $l->servicios)->map(fn ($a) => $a->limiteDelPlan($l->api_plan_id))->max() ?? 0
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

                    <p class="mt-1 font-mono text-xs text-gray-400">{{ $l->clave }}</p>
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

                <div class="flex shrink-0 items-center gap-1.5">
                    <button type="button"
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
                            ]) }}; nueva = false"
                            class="rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                        Editar
                    </button>

                    <form method="POST" action="{{ route('super-admin.consultas.llaves.alternar', $l) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-lg border px-2.5 py-1.5 text-xs font-medium transition
                                       {{ $l->activa
                                            ? 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                                            : 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100' }}">
                            {{ $l->activa ? 'Bloquear' : 'Desbloquear' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('super-admin.consultas.llaves.borrar', $l) }}"
                          onsubmit="return confirm('Se elimina «{{ $l->nombre }}». Quien la use dejará de tener acceso al instante. ¿Continuar?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
                            Borrar
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="px-5 py-12 text-center">
                <p class="text-sm text-gray-500">Todavía no hay ninguna llave.</p>
                <p class="mt-1 text-xs text-gray-400">Crea una para que alguien pueda usar las consultas.</p>
            </div>
        @endforelse
    </section>

    {{-- Crear y editar en modal: son siete campos y meterlos en la fila la
         volveria ilegible. --}}
    <div x-show="llave || nueva" x-cloak
         @keydown.escape.window="llave = null; nueva = false"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-6">
        <div @click.outside="llave = null; nueva = false"
             class="my-auto w-full max-w-lg rounded-xl bg-white shadow-xl">

            <form method="POST"
                  :action="nueva
                      ? '{{ route('super-admin.consultas.llaves.guardar') }}'
                      : '{{ url('super-admin/consultas/llaves') }}/' + (llave?.id ?? '')"
                  x-data="{ tipo: 'empresa' }"
                  x-effect="tipo = llave?.titular_tipo ?? 'empresa'">
                @csrf
                <template x-if="!nueva"><input type="hidden" name="_method" value="PUT"></template>

                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900" x-text="nueva ? 'Nueva llave' : 'Editar llave'"></h3>
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
                            Si le roban esta llave, solo podrán usar lo que esté marcado.
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

                <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
                    <button type="button" @click="llave = null; nueva = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
