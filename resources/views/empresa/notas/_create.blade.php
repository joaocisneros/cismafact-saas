<div class="space-y-5"
     x-data="notaForm({
        clientes: {{ Js::from($clients->map(fn($c) => ['id'=>$c->id,'tipo'=>$c->tipo_documento,'doc'=>$c->numero_documento,'nombre'=>$c->razon_social,'dir'=>$c->direccion,'email'=>$c->email])) }},
        referencias: {{ Js::from($referencias) }},
        moneda: 'PEN'
     })">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Nueva {{ $titulo }}</h1>
            <p class="text-gray-500 mt-1">Referencia un comprobante y emite directo a SUNAT.</p>
        </div>
        <a href="{{ route('empresa.' . $routePrefix . '.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver</a>
    </div>

    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3">
            <ul class="list-disc list-inside text-sm space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('empresa.' . $routePrefix . '.store') }}" @submit="enviando = true" class="space-y-5">
        @csrf
        <input type="hidden" name="branch_id" value="{{ $branch->id }}">

        {{-- Cabecera + documento afectado --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 grid gap-4 md:grid-cols-3">
            <label class="text-sm font-medium text-gray-700">Serie de la nota
                <select name="serie" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    @foreach($series as $s)
                        <option value="{{ $s->serie }}">
                            {{ $s->serie }} (siguiente: {{ $s->correlativo_actual + 1 }})@if($series->pluck('branch_id')->unique()->count() > 1) — {{ $s->sucursal_nombre }}@endif
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-gray-700">Fecha emisión
                <input type="date" name="fecha_emision" value="{{ old('fecha_emision', now()->toDateString()) }}" required
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
            <label class="text-sm font-medium text-gray-700">Moneda
                <x-select-moneda name="moneda" x-model="moneda" />
            </label>

            <label class="text-sm font-medium text-gray-700 md:col-span-1">Comprobante afectado
                <select @change="seleccionarReferencia($event.target.value)" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">— Elegir comprobante —</option>
                    <template x-for="(r, idx) in referencias" :key="idx">
                        <option :value="idx" x-text="(r.tipo === '01' ? 'Factura ' : 'Boleta ') + r.num"></option>
                    </template>
                </select>
            </label>
            <label class="text-sm font-medium text-gray-700">Tipo doc. afectado
                <select name="tipo_doc_afectado" x-model="tipoAfectado" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="01">Factura</option>
                    <option value="03">Boleta</option>
                </select>
            </label>
            <label class="text-sm font-medium text-gray-700">N° doc. afectado
                <input name="num_doc_afectado" x-model="numAfectado" required placeholder="F001-123"
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>

            <label class="text-sm font-medium text-gray-700">Motivo
                <select name="cod_motivo" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    @foreach($motivos as $cod => $label)
                        <option value="{{ $cod }}">{{ $cod }} - {{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-gray-700 md:col-span-2">Descripción del motivo
                <input name="des_motivo" required maxlength="250" value="{{ old('des_motivo') }}"
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
        </div>

        {{-- Cliente --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Cliente</h2>
                {{-- Ancho fijo: el texto de dentro cambia con el tipo elegido, y
                     sin esto la caja crecia y encogia moviendo lo de al lado. --}}
                <select x-model="clienteElegido" @change="seleccionarCliente($event.target.value)"
                        class="w-72 max-w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    {{-- Solo los del tipo que está puesto al lado: elegir aquí un
                         cliente de otro tipo dejaba el formulario descuadrado, y
                         eso no se veía hasta darle a emitir. --}}
                    <option value="" x-text="clientesDelTipo.length ? '— Cliente frecuente —' : '— Ninguno de ese tipo —'"></option>
                    <template x-for="c in clientesDelTipo" :key="c.id">
                        <option :value="c.id" x-text="c.doc + ' - ' + c.nombre"></option>
                    </template>
                </select>
            </div>
            <div class="grid gap-4 md:grid-cols-4">
                <label class="text-sm font-medium text-gray-700">Tipo doc.
                    <x-select-tipo-documento name="client[tipo_documento]" x-model="cliente.tipo" @change="cambiarTipo()" />
                </label>
                <label class="text-sm font-medium text-gray-700">N° documento
                    <input name="client[numero_documento]" x-model="cliente.doc" required maxlength="15" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="text-sm font-medium text-gray-700 md:col-span-2">Nombre / Razón social
                    <input name="client[razon_social]" x-model="cliente.nombre" required maxlength="255" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="text-sm font-medium text-gray-700 md:col-span-2">Dirección
                    <input name="client[direccion]" x-model="cliente.dir" maxlength="255" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="text-sm font-medium text-gray-700 md:col-span-2">Email
                    <input type="email" name="client[email]" x-model="cliente.email" maxlength="100" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
            </div>
        </div>

        {{-- Items --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Detalle</h2>
                <button type="button" @click="agregarItem()" class="rounded-md bg-blue-50 text-blue-700 px-3 py-1.5 text-sm font-medium hover:bg-blue-100">+ Agregar ítem</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="py-2 pr-2">Código</th>
                            <th class="py-2 pr-2 w-1/3">Descripción</th>
                            <th class="py-2 pr-2">Und.</th>
                            <th class="py-2 pr-2">Cant.</th>
                            <th class="py-2 pr-2">V. Unit.</th>
                            <th class="py-2 pr-2">Afectación</th>
                            <th class="py-2 pr-2 text-right">Importe</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, i) in items" :key="i">
                            <tr class="border-t border-gray-100">
                                <td class="py-2 pr-2"><input :name="`detalles[${i}][codigo]`" x-model="item.codigo" required class="w-20 rounded border border-gray-300 px-2 py-1.5"></td>
                                <td class="py-2 pr-2"><input :name="`detalles[${i}][descripcion]`" x-model="item.descripcion" required class="w-full rounded border border-gray-300 px-2 py-1.5"></td>
                                <td class="py-2 pr-2">
                                    <select :name="`detalles[${i}][unidad]`" x-model="item.unidad" class="rounded border border-gray-300 px-2 py-1.5">
                                        <option value="NIU">NIU</option>
                                        <option value="ZZ">ZZ</option>
                                        <option value="KGM">KGM</option>
                                    </select>
                                </td>
                                <td class="py-2 pr-2"><input type="number" step="0.001" min="0.001" :name="`detalles[${i}][cantidad]`" x-model.number="item.cantidad" required class="w-20 rounded border border-gray-300 px-2 py-1.5"></td>
                                <td class="py-2 pr-2"><input type="number" step="0.01" min="0" :name="`detalles[${i}][mto_valor_unitario]`" x-model.number="item.valorUnitario" required class="w-24 rounded border border-gray-300 px-2 py-1.5"></td>
                                <td class="py-2 pr-2">
                                    <x-select-afectacion :excluir="['17']" ::name="`detalles[${i}][tip_afe_igv]`" x-model="item.afectacion" />
                                    {{-- No solo la 10: un retiro o una bonificacion gravada tampoco se
                                         cobra, pero paga IGV igual sobre su valor referencial. La 17
                                         queda fuera porque el IVAP lleva su propia tasa. --}}
                                    <input type="hidden" :name="`detalles[${i}][porcentaje_igv]`"
                                           :value="['10','11','12','13','14','15','16'].includes(item.afectacion) ? 18 : 0">
                                </td>
                                <td class="py-2 pr-2 text-right font-medium" x-text="formato(item.cantidad * item.valorUnitario)"></td>
                                <td class="py-2 text-right"><button type="button" @click="quitarItem(i)" class="text-red-500 hover:text-red-700" x-show="items.length > 1">✕</button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end">
                <div class="w-full max-w-xs space-y-1 text-sm">
                    <div class="flex justify-between text-gray-600"><span>Op. gravadas</span><span x-text="simbolo + ' ' + formato(totales.gravadas)"></span></div>
                    {{-- Aparte del total: lo gratuito se declara pero no se cobra,
                         asi que sumarlo daria un importe que el cliente no paga. --}}
                    <div class="flex justify-between text-gray-600" x-show="totales.gratuitas > 0"><span>Op. gratuitas</span><span x-text="simbolo + ' ' + formato(totales.gratuitas)"></span></div>
                    <div class="flex justify-between text-gray-600"><span>IGV (18%)</span><span x-text="simbolo + ' ' + formato(totales.igv)"></span></div>
                    <div class="flex justify-between font-bold text-gray-900 text-base border-t border-gray-200 pt-1"><span>Total</span><span x-text="simbolo + ' ' + formato(totales.total)"></span></div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('empresa.' . $routePrefix . '.index') }}" class="rounded-md border border-gray-300 px-5 py-2.5 text-sm">Cancelar</a>
            {{-- Se bloquea al enviar: emitir tarda lo que tarde SUNAT, y sin esto
                 un segundo clic mientras se espera emite el comprobante dos
                 veces y quema un correlativo que luego hay que anular. --}}
            <button type="submit" :disabled="enviando"
                    class="rounded-md bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-400">
                <span x-text="enviando ? 'Enviando a SUNAT…' : 'Emitir y enviar a SUNAT'"></span>
            </button>
        </div>
    </form>
</div>

<script>
function notaForm(config) {
    return {
        clientes: config.clientes,
        clienteElegido: '',
        referencias: config.referencias,
        moneda: config.moneda,
        tipoAfectado: '01',
        numAfectado: '',
        cliente: { tipo: '6', doc: '', nombre: '', dir: '', email: '' },
        enviando: false,
        items: [{ codigo: '001', descripcion: '', unidad: 'NIU', cantidad: 1, valorUnitario: 0, afectacion: '10' }],
        get simbolo() {
            // Las que no tienen simbolo conocido salen con su codigo: mejor
            // «BRL 100.00» que un «S/ 100.00» que no es verdad.
            return { PEN: 'S/', USD: '$', EUR: '\u20ac', GBP: '\u00a3', JPY: '\u00a5', CNY: '\u00a5' }[this.moneda] || this.moneda;
        },
        get totales() {
            let gravadas = 0, exoneradas = 0, inafectas = 0, exportacion = 0, gratuitas = 0, igv = 0;
            this.items.forEach(it => {
                const base = (Number(it.cantidad) || 0) * (Number(it.valorUnitario) || 0);

                // Lo que no se cobra va aparte y no suma al total a pagar: se
                // declara, pero el cliente no lo paga.
                if (['11','12','13','14','15','16','21','31','32','33','34','35','36'].includes(it.afectacion)) {
                    gratuitas += base;
                    if (it.afectacion !== '21' && it.afectacion[0] === '1') igv += base * 0.18;
                    return;
                }

                if (it.afectacion === '10') { gravadas += base; igv += base * 0.18; }
                else if (it.afectacion === '20') { exoneradas += base; }
                else if (it.afectacion === '30') { inafectas += base; }
                else if (it.afectacion === '40') { exportacion += base; }
            });
            const total = gravadas + exoneradas + inafectas + exportacion + igv;
            return { gravadas, exoneradas, inafectas, exportacion, gratuitas, igv, total };
        },
        seleccionarReferencia(idx) {
            const r = this.referencias[idx];
            if (!r) return;
            this.tipoAfectado = r.tipo;
            this.numAfectado = r.num;
            const c = this.clientes.find(x => String(x.id) === String(r.client_id));
            if (c) this.cliente = { tipo: c.tipo, doc: c.doc, nombre: c.nombre, dir: c.dir || '', email: c.email || '' };
        },
        get clientesDelTipo() {
            return this.clientes.filter(c => String(c.tipo) === String(this.cliente.tipo));
        },
        /** Al cambiar de tipo, el elegido ya no vale: era de la otra lista. */
        cambiarTipo() {
            this.clienteElegido = '';
            this.cliente.doc = '';
            this.cliente.nombre = '';
        },
        seleccionarCliente(id) {
            const c = this.clientes.find(x => String(x.id) === String(id));
            if (c) this.cliente = { tipo: c.tipo, doc: c.doc, nombre: c.nombre, dir: c.dir || '', email: c.email || '' };
        },
        agregarItem() {
            // Uno mas que el mayor, no uno mas que la cuenta: al quitar un item
            // de en medio, contar los que quedan repetia un codigo ya usado.
            const siguiente = this.items.reduce((mayor, it) => Math.max(mayor, Number(it.codigo) || 0), 0) + 1;
            this.items.push({ codigo: String(siguiente).padStart(3, '0'), descripcion: '', unidad: 'NIU', cantidad: 1, valorUnitario: 0, afectacion: '10' });
        },
        quitarItem(i) { if (this.items.length > 1) this.items.splice(i, 1); },
        formato(n) { return (Number(n) || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    };
}
</script>
