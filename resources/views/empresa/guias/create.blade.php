@extends('layouts.app')

@section('title', 'Nueva Guía de Remisión')

@section('content')
<div class="space-y-5"
     x-data="guiaForm({
        clientes: {{ Js::from($clients->map(fn($c) => ['id'=>$c->id,'tipo'=>$c->tipo_documento,'doc'=>$c->numero_documento,'nombre'=>$c->razon_social,'dir'=>$c->direccion,'email'=>$c->email])) }}
     })">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Nueva Guía de Remisión</h1>
            <p class="text-gray-500 mt-1">Registra el traslado y emítelo a SUNAT (GRE).</p>
        </div>
        <a href="{{ route('empresa.guias.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver</a>
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

    <form method="POST" action="{{ route('empresa.guias.store') }}" @submit="enviando = true" class="space-y-5">
        @csrf
        <input type="hidden" name="branch_id" value="{{ $branch->id }}">

        {{-- Cabecera --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 grid gap-4 md:grid-cols-3">
            <label class="text-sm font-medium text-gray-700">Serie
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
            <label class="text-sm font-medium text-gray-700">Fecha de traslado
                <input type="date" name="fecha_traslado" value="{{ old('fecha_traslado', now()->toDateString()) }}" required
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
        </div>

        {{-- Destinatario --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Destinatario</h2>
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
                    <x-select-tipo-documento name="destinatario[tipo_documento]" x-model="dest.tipo" @change="cambiarTipo()" />
                </label>
                <label class="text-sm font-medium text-gray-700">N° documento
                    <input name="destinatario[numero_documento]" x-model="dest.doc" required maxlength="15" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="text-sm font-medium text-gray-700 md:col-span-2">Razón social / Nombre
                    <input name="destinatario[razon_social]" x-model="dest.nombre" required maxlength="255" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="text-sm font-medium text-gray-700 md:col-span-2">Dirección (opcional)
                    <input name="destinatario[direccion]" x-model="dest.dir" maxlength="255" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="text-sm font-medium text-gray-700 md:col-span-2">Email (opcional)
                    <input type="email" name="destinatario[email]" x-model="dest.email" maxlength="100" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
            </div>
        </div>

        {{-- Traslado --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 grid gap-4 md:grid-cols-3">
            <label class="text-sm font-medium text-gray-700">Motivo de traslado
                <select name="cod_traslado" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    @foreach($motivos as $cod => $label)
                        <option value="{{ $cod }}">{{ $cod }} - {{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-gray-700">Peso total
                <input type="number" step="0.001" min="0.001" name="peso_total" value="{{ old('peso_total', 1) }}" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
            <label class="text-sm font-medium text-gray-700">Unidad de peso
                <select name="und_peso_total" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="KGM">KGM (Kilogramos)</option>
                    <option value="TNE">TNE (Toneladas)</option>
                </select>
            </label>

            <label class="text-sm font-medium text-gray-700">Ubigeo partida
                <input name="partida_ubigeo" x-model="partidaUbigeo" required maxlength="6" placeholder="150101" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
            <label class="text-sm font-medium text-gray-700 md:col-span-2">Dirección de partida
                <input name="partida_direccion" required maxlength="255" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
            <label class="text-sm font-medium text-gray-700">Ubigeo llegada
                <input name="llegada_ubigeo" required maxlength="6" placeholder="150101" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
            <label class="text-sm font-medium text-gray-700 md:col-span-2">Dirección de llegada
                <input name="llegada_direccion" required maxlength="255" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
        </div>

        {{-- Modalidad de transporte --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
            <h2 class="font-semibold text-gray-800">Transporte</h2>
            <label class="text-sm font-medium text-gray-700 block">Modalidad
                <select name="mod_traslado" x-model="modalidad" class="mt-1 w-full md:w-1/2 rounded-md border border-gray-300 px-3 py-2">
                    @foreach($modalidades as $cod => $label)
                        <option value="{{ $cod }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            {{-- Público (01): transportista --}}
            <div x-show="modalidad === '01'" class="grid gap-4 md:grid-cols-3 border-t border-gray-100 pt-4">
                <label class="text-sm font-medium text-gray-700">RUC transportista
                    <input name="transportista_num_doc" maxlength="11" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <input type="hidden" name="transportista_tipo_doc" value="6">
                </label>
                <label class="text-sm font-medium text-gray-700 md:col-span-2">Razón social transportista
                    <input name="transportista_razon_social" maxlength="255" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="text-sm font-medium text-gray-700">N° registro MTC (opcional)
                    <input name="transportista_nro_mtc" maxlength="20" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
            </div>

            {{-- Privado (02): vehículo + conductor --}}
            <div x-show="modalidad === '02'" class="grid gap-4 md:grid-cols-3 border-t border-gray-100 pt-4">
                <label class="text-sm font-medium text-gray-700">Placa del vehículo
                    <input name="vehiculo_placa" maxlength="8" placeholder="ABC123" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="text-sm font-medium text-gray-700">Tipo doc. conductor
                    <select name="conductor_tipo_doc" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                        <option value="1">DNI</option>
                        <option value="4">Carnet ext.</option>
                        <option value="6">RUC</option>
                        <option value="7">Pasaporte</option>
                    </select>
                </label>
                <label class="text-sm font-medium text-gray-700">N° documento conductor
                    <input name="conductor_num_doc" maxlength="15" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="text-sm font-medium text-gray-700">Licencia de conducir
                    <input name="conductor_licencia" maxlength="20" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="text-sm font-medium text-gray-700">Nombres conductor
                    <input name="conductor_nombres" maxlength="100" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <label class="text-sm font-medium text-gray-700">Apellidos conductor
                    <input name="conductor_apellidos" maxlength="100" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
            </div>
        </div>

        {{-- Bienes --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Bienes a trasladar</h2>
                <button type="button" @click="agregarItem()" class="rounded-md bg-blue-50 text-blue-700 px-3 py-1.5 text-sm font-medium hover:bg-blue-100">+ Agregar bien</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="py-2 pr-2">Código</th>
                            <th class="py-2 pr-2 w-1/2">Descripción</th>
                            <th class="py-2 pr-2">Und.</th>
                            <th class="py-2 pr-2">Cantidad</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, i) in items" :key="i">
                            <tr class="border-t border-gray-100">
                                <td class="py-2 pr-2"><input :name="`detalles[${i}][codigo]`" x-model="item.codigo" required class="w-24 rounded border border-gray-300 px-2 py-1.5"></td>
                                <td class="py-2 pr-2"><input :name="`detalles[${i}][descripcion]`" x-model="item.descripcion" required class="w-full rounded border border-gray-300 px-2 py-1.5"></td>
                                <td class="py-2 pr-2">
                                    <select :name="`detalles[${i}][unidad]`" x-model="item.unidad" class="rounded border border-gray-300 px-2 py-1.5">
                                        <option value="NIU">NIU</option>
                                        <option value="KGM">KGM</option>
                                        <option value="ZZ">ZZ</option>
                                    </select>
                                </td>
                                <td class="py-2 pr-2"><input type="number" step="0.001" min="0.001" :name="`detalles[${i}][cantidad]`" x-model.number="item.cantidad" required class="w-24 rounded border border-gray-300 px-2 py-1.5"></td>
                                <td class="py-2 text-right"><button type="button" @click="quitarItem(i)" class="text-red-500 hover:text-red-700" x-show="items.length > 1">✕</button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <label class="text-sm font-medium text-gray-700 block">Observaciones (opcional)
                <textarea name="observaciones" rows="2" maxlength="250" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"></textarea>
            </label>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('empresa.guias.index') }}" class="rounded-md border border-gray-300 px-5 py-2.5 text-sm">Cancelar</a>
            {{-- Se bloquea al enviar: emitir tarda lo que tarde SUNAT, y sin esto
                 un segundo clic mientras se espera emite la guia dos veces y
                 quema un correlativo que luego hay que anular. --}}
            <button type="submit" :disabled="enviando"
                    class="rounded-md bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-400">
                <span x-text="enviando ? 'Enviando a SUNAT…' : 'Emitir y enviar a SUNAT'"></span>
            </button>
        </div>
    </form>
</div>

<script>
function guiaForm(config) {
    return {
        clientes: config.clientes,
        clienteElegido: '',
        enviando: false,
        modalidad: '01',
        partidaUbigeo: '',
        dest: { tipo: '6', doc: '', nombre: '', dir: '', email: '' },
        items: [{ codigo: '001', descripcion: '', unidad: 'NIU', cantidad: 1 }],
        get clientesDelTipo() {
            return this.clientes.filter(c => String(c.tipo) === String(this.dest.tipo));
        },
        /** Al cambiar de tipo, el elegido ya no vale: era de la otra lista. */
        cambiarTipo() {
            this.clienteElegido = '';
            this.dest.doc = '';
            this.dest.nombre = '';
        },
        seleccionarCliente(id) {
            const c = this.clientes.find(x => String(x.id) === String(id));
            if (c) this.dest = { tipo: c.tipo, doc: c.doc, nombre: c.nombre, dir: c.dir || '', email: c.email || '' };
        },
        agregarItem() {
            // Uno mas que el mayor, no uno mas que la cuenta: al quitar un item
            // de en medio, contar los que quedan repetia un codigo ya usado.
            const siguiente = this.items.reduce((mayor, it) => Math.max(mayor, Number(it.codigo) || 0), 0) + 1;
            this.items.push({ codigo: String(siguiente).padStart(3, '0'), descripcion: '', unidad: 'NIU', cantidad: 1 });
        },
        quitarItem(i) { if (this.items.length > 1) this.items.splice(i, 1); },
    };
}
</script>
@endsection
