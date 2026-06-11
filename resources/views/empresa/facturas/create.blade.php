@extends('layouts.app')

@section('title', 'Nueva Factura')

@section('content')
<div class="space-y-5"
     x-data="facturaForm({
        clientes: {{ Js::from($clients->map(fn($c) => ['id'=>$c->id,'tipo'=>$c->tipo_documento,'doc'=>$c->numero_documento,'nombre'=>$c->razon_social,'dir'=>$c->direccion,'email'=>$c->email])) }},
        moneda: 'PEN'
     })">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Nueva Factura</h1>
            <p class="text-gray-500 mt-1">Completa los datos y emite directo a SUNAT.</p>
        </div>
        <a href="{{ route('empresa.facturas.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver</a>
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

    <form method="POST" action="{{ route('empresa.facturas.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="branch_id" value="{{ $branch->id }}">

        {{-- Cabecera --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 grid gap-4 md:grid-cols-4">
            <label class="text-sm font-medium text-gray-700">Serie
                <select name="serie" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    @foreach($series as $s)
                        <option value="{{ $s->serie }}">{{ $s->serie }} (siguiente: {{ $s->correlativo_actual + 1 }})</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-gray-700">Fecha emisión
                <input type="date" name="fecha_emision" value="{{ old('fecha_emision', now()->toDateString()) }}" required
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
            <label class="text-sm font-medium text-gray-700">Moneda
                <select name="moneda" x-model="moneda" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="PEN">PEN (Soles)</option>
                    <option value="USD">USD (Dólares)</option>
                </select>
            </label>
            <label class="text-sm font-medium text-gray-700">Forma de pago
                <select name="forma_pago_tipo" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="Contado">Contado</option>
                    <option value="Credito">Crédito</option>
                </select>
            </label>
        </div>

        {{-- Cliente --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Cliente</h2>
                <select @change="seleccionarCliente($event.target.value)" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">— Cliente frecuente (RUC) —</option>
                    <template x-for="c in clientesRuc" :key="c.id">
                        <option :value="c.id" x-text="c.doc + ' - ' + c.nombre"></option>
                    </template>
                </select>
            </div>
            <p class="rounded-md bg-blue-50 border border-blue-100 px-3 py-2 text-xs text-blue-700">
                Las facturas solo se emiten a clientes con <strong>RUC</strong>. Si tu cliente tiene DNI, emite una <strong>Boleta</strong>.
            </p>
            <div class="grid gap-4 md:grid-cols-4">
                <label class="text-sm font-medium text-gray-700">Tipo doc.
                    <select name="client[tipo_documento]" x-model="cliente.tipo" class="mt-1 w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2">
                        <option value="6">RUC</option>
                    </select>
                </label>
                <label class="text-sm font-medium text-gray-700">RUC
                    <input name="client[numero_documento]" x-model="cliente.doc" required maxlength="11" inputmode="numeric"
                           pattern="\d{11}" placeholder="20123456789"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="text-sm font-medium text-gray-700 md:col-span-2">Razón social / Nombre
                    <input name="client[razon_social]" x-model="cliente.nombre" required maxlength="255"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="text-sm font-medium text-gray-700 md:col-span-2">Dirección
                    <input name="client[direccion]" x-model="cliente.dir" maxlength="255"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="text-sm font-medium text-gray-700 md:col-span-2">Email (para enviar el PDF)
                    <input type="email" name="client[email]" x-model="cliente.email" maxlength="100"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
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
                                        <option value="ZZ">ZZ (Servicio)</option>
                                        <option value="KGM">KGM</option>
                                        <option value="MTR">MTR</option>
                                        <option value="LTR">LTR</option>
                                    </select>
                                </td>
                                <td class="py-2 pr-2"><input type="number" step="0.001" min="0.001" :name="`detalles[${i}][cantidad]`" x-model.number="item.cantidad" required class="w-20 rounded border border-gray-300 px-2 py-1.5"></td>
                                <td class="py-2 pr-2"><input type="number" step="0.01" min="0" :name="`detalles[${i}][mto_valor_unitario]`" x-model.number="item.valorUnitario" required class="w-24 rounded border border-gray-300 px-2 py-1.5"></td>
                                <td class="py-2 pr-2">
                                    <select :name="`detalles[${i}][tip_afe_igv]`" x-model="item.afectacion" class="rounded border border-gray-300 px-2 py-1.5">
                                        <option value="10">Gravado (IGV 18%)</option>
                                        <option value="20">Exonerado</option>
                                        <option value="30">Inafecto</option>
                                        <option value="40">Exportación</option>
                                    </select>
                                    <input type="hidden" :name="`detalles[${i}][porcentaje_igv]`" :value="item.afectacion === '10' ? 18 : 0">
                                </td>
                                <td class="py-2 pr-2 text-right font-medium" x-text="formato(item.cantidad * item.valorUnitario)"></td>
                                <td class="py-2 text-right"><button type="button" @click="quitarItem(i)" class="text-red-500 hover:text-red-700" x-show="items.length > 1">✕</button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Totales --}}
            <div class="flex justify-end">
                <div class="w-full max-w-xs space-y-1 text-sm">
                    <div class="flex justify-between text-gray-600"><span>Op. gravadas</span><span x-text="simbolo + ' ' + formato(totales.gravadas)"></span></div>
                    <div class="flex justify-between text-gray-600" x-show="totales.exoneradas > 0"><span>Op. exoneradas</span><span x-text="simbolo + ' ' + formato(totales.exoneradas)"></span></div>
                    <div class="flex justify-between text-gray-600" x-show="totales.inafectas > 0"><span>Op. inafectas</span><span x-text="simbolo + ' ' + formato(totales.inafectas)"></span></div>
                    <div class="flex justify-between text-gray-600"><span>IGV (18%)</span><span x-text="simbolo + ' ' + formato(totales.igv)"></span></div>
                    <div class="flex justify-between font-bold text-gray-900 text-base border-t border-gray-200 pt-1"><span>Total</span><span x-text="simbolo + ' ' + formato(totales.total)"></span></div>
                </div>
            </div>
        </div>

        {{-- Observación --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <label class="text-sm font-medium text-gray-700">Observación (opcional)
                <textarea name="observacion" rows="2" maxlength="500" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">{{ old('observacion') }}</textarea>
            </label>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('empresa.facturas.index') }}" class="rounded-md border border-gray-300 px-5 py-2.5 text-sm">Cancelar</a>
            <button class="rounded-md bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">Emitir y enviar a SUNAT</button>
        </div>
    </form>
</div>

<script>
function facturaForm(config) {
    return {
        clientes: config.clientes,
        moneda: config.moneda,
        cliente: { tipo: '6', doc: '', nombre: '', dir: '', email: '' },
        items: [{ codigo: '001', descripcion: '', unidad: 'NIU', cantidad: 1, valorUnitario: 0, afectacion: '10' }],

        get simbolo() { return this.moneda === 'USD' ? '$' : 'S/'; },

        get clientesRuc() { return this.clientes.filter(c => String(c.tipo) === '6'); },

        get totales() {
            let gravadas = 0, exoneradas = 0, inafectas = 0, exportacion = 0, igv = 0;
            this.items.forEach(it => {
                const base = (Number(it.cantidad) || 0) * (Number(it.valorUnitario) || 0);
                if (it.afectacion === '10') { gravadas += base; igv += base * 0.18; }
                else if (it.afectacion === '20') { exoneradas += base; }
                else if (it.afectacion === '30') { inafectas += base; }
                else if (it.afectacion === '40') { exportacion += base; }
            });
            const total = gravadas + exoneradas + inafectas + exportacion + igv;
            return { gravadas, exoneradas, inafectas, exportacion, igv, total };
        },

        seleccionarCliente(id) {
            const c = this.clientes.find(x => String(x.id) === String(id));
            if (c) this.cliente = { tipo: '6', doc: c.doc, nombre: c.nombre, dir: c.dir || '', email: c.email || '' };
        },
        agregarItem() {
            this.items.push({ codigo: String(this.items.length + 1).padStart(3, '0'), descripcion: '', unidad: 'NIU', cantidad: 1, valorUnitario: 0, afectacion: '10' });
        },
        quitarItem(i) { if (this.items.length > 1) this.items.splice(i, 1); },
        formato(n) { return (Number(n) || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    };
}
</script>
@endsection
