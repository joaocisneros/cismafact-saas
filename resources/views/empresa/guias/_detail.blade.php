@php
    $estadoClass = match($guia->estado_sunat) {
        'ACEPTADO' => 'bg-green-50 text-green-700 border-green-200',
        'RECHAZADO' => 'bg-red-50 text-red-700 border-red-200',
        'ANULADO' => 'bg-gray-100 text-gray-600 border-gray-200',
        'PROCESANDO' => 'bg-blue-50 text-blue-700 border-blue-200',
        default => 'bg-amber-50 text-amber-700 border-amber-200',
    };
    $respuesta = is_string($guia->respuesta_sunat) ? json_decode($guia->respuesta_sunat, true) : ($guia->respuesta_sunat ?? []);
    $mensajeSunat = $respuesta['description'] ?? $respuesta['message'] ?? null;
    $partida = $guia->partida ?? [];
    $llegada = $guia->llegada ?? [];
    $modal = $modal ?? false;
@endphp
<div class="space-y-5 {{ $modal ? 'p-5' : 'max-w-4xl' }}">
    <div class="flex items-center justify-between">
        <div>
            @unless($modal)
                <a href="{{ route('empresa.guias.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver a guías</a>
            @endunless
            <h1 class="text-xl font-bold text-gray-800 {{ $modal ? '' : 'mt-1 text-2xl' }}">Guía {{ $guia->serie }}-{{ $guia->correlativo }}</h1>
        </div>
        <span class="rounded-full border px-3 py-1 text-sm font-medium {{ $estadoClass }}">{{ $guia->estado_sunat ?? 'PENDIENTE' }}</span>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap gap-2">
        @if($guia->pdf_path || $guia->estado_sunat === 'ACEPTADO')
            <a href="{{ route('empresa.documents.download', ['guia_remision', $guia->id, 'pdf']) }}" class="rounded-md bg-rose-600 text-white px-4 py-2 text-sm font-medium hover:bg-rose-700">Descargar PDF</a>
        @endif
        @if($guia->xml_path)
            <a href="{{ route('empresa.documents.download', ['guia_remision', $guia->id, 'xml']) }}" class="rounded-md bg-gray-100 text-gray-700 px-4 py-2 text-sm font-medium hover:bg-gray-200">XML</a>
        @endif
        @if($guia->cdr_path)
            <a href="{{ route('empresa.documents.download', ['guia_remision', $guia->id, 'cdr']) }}" class="rounded-md bg-gray-100 text-gray-700 px-4 py-2 text-sm font-medium hover:bg-gray-200">CDR</a>
        @endif
        @if($guia->estado_sunat !== 'ACEPTADO')
            <form method="POST" action="{{ route('empresa.guias.send-sunat', $guia->id) }}">
                @csrf
                <button class="rounded-md bg-blue-600 text-white px-4 py-2 text-sm font-medium hover:bg-blue-700">{{ $guia->ticket ? 'Reenviar a SUNAT' : 'Enviar a SUNAT' }}</button>
            </form>
        @endif
        @if($guia->ticket && $guia->estado_sunat !== 'ACEPTADO')
            <form method="POST" action="{{ route('empresa.guias.check-status', $guia->id) }}">
                @csrf
                <button class="rounded-md bg-emerald-600 text-white px-4 py-2 text-sm font-medium hover:bg-emerald-700">Consultar estado SUNAT</button>
            </form>
        @endif
    </div>

    @if($guia->ticket || $mensajeSunat)
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm space-y-1">
            @if($guia->ticket)
                <div class="flex justify-between gap-3"><span class="text-gray-500">Ticket SUNAT</span><span class="font-mono text-gray-800">{{ $guia->ticket }}</span></div>
            @endif
            @if($mensajeSunat)
                <div class="flex justify-between gap-3"><span class="text-gray-500">Respuesta</span><span class="text-gray-800 text-right">{{ $mensajeSunat }}</span></div>
            @endif
        </div>
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Destinatario</h2>
            <p class="text-sm text-gray-900 font-medium">{{ $guia->client?->razon_social }}</p>
            <p class="text-sm text-gray-600">{{ $guia->client?->numero_documento }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Traslado</h2>
            <dl class="text-sm space-y-1">
                <div class="flex justify-between"><dt class="text-gray-500">Motivo</dt><dd class="text-gray-800">{{ $motivos[$guia->cod_traslado] ?? $guia->cod_traslado }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Modalidad</dt><dd class="text-gray-800">{{ $modalidades[$guia->mod_traslado] ?? $guia->mod_traslado }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Peso</dt><dd class="text-gray-800">{{ $guia->peso_total }} {{ $guia->und_peso_total }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Fecha traslado</dt><dd class="text-gray-800">{{ \Illuminate\Support\Carbon::parse($guia->fecha_traslado)->format('d/m/Y') }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <h2 class="font-semibold text-gray-800 mb-2">Punto de partida</h2>
            <p class="text-sm text-gray-700">{{ $partida['direccion'] ?? '' }}</p>
            <p class="text-xs text-gray-400">Ubigeo: {{ $partida['ubigeo'] ?? '' }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <h2 class="font-semibold text-gray-800 mb-2">Punto de llegada</h2>
            <p class="text-sm text-gray-700">{{ $llegada['direccion'] ?? '' }}</p>
            <p class="text-xs text-gray-400">Ubigeo: {{ $llegada['ubigeo'] ?? '' }}</p>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Descripción</th>
                    <th class="px-4 py-3">Unidad</th>
                    <th class="px-4 py-3 text-right">Cantidad</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach(($guia->detalles ?? []) as $d)
                    <tr>
                        <td class="px-4 py-3 text-gray-800">{{ $d['descripcion'] ?? '' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $d['unidad'] ?? '' }}</td>
                        <td class="px-4 py-3 text-right text-gray-800">{{ $d['cantidad'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Baja de la guia. SUNAT no permite anularla desde el sistema del
     contribuyente: se hace en su portal con la Clave SOL. Aqui solo se deja
     constancia para que no siga figurando como vigente. --}}
<div class="border-t border-gray-200 p-5">
    @if($guia->anulado_en)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">Dada de baja</p>
            <p class="mt-1">
                Registrada el {{ $guia->anulado_en->format('d/m/Y H:i') }}
                @if($guia->anulado_registrado_por) por {{ $guia->anulado_registrado_por }} @endif.
                Motivo: {{ $guia->anulado_motivo }}
            </p>
        </div>
    @elseif($guia->estado_sunat === 'ACEPTADO')
        <div x-data="{ abierto: false }">
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">Para anular esta guía</p>
                <p class="mt-1">
                    SUNAT no permite dar de baja una guía desde ningún sistema externo: se hace en
                    <strong>su portal</strong> con tu Clave SOL, en Empresas → Guía de Remisión Electrónica → Baja de GRE.
                    Solo procede si el traslado no ha comenzado, o si cambió el destinatario antes de llegar.
                </p>
                <p class="mt-2">
                    <a href="https://cpe.sunat.gob.pe/node/118" target="_blank" rel="noopener"
                       class="font-medium underline">Ver la guía de SUNAT</a>
                    ·
                    <button type="button" @click="abierto = ! abierto" class="font-medium underline">
                        Ya la di de baja allí, registrarlo aquí
                    </button>
                </p>
            </div>

            <form x-show="abierto" x-cloak method="POST" class="mt-3 space-y-3"
                  action="{{ route('empresa.guias.registrar-baja', $guia->id) }}"
                  data-success-message="Baja registrada.">
                @csrf
                <label class="block text-sm font-medium text-gray-700">Motivo de la baja
                    <input name="motivo" required minlength="3" maxlength="250"
                           placeholder="Ej.: el traslado no se realizó"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>
                <p class="text-xs text-gray-500">
                    Esto no envía nada a SUNAT: solo deja constancia de la baja que ya hiciste en su portal.
                </p>
                <div class="flex justify-end">
                    <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                        Registrar la baja
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
