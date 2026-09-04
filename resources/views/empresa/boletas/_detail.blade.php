@php
    $estadoClass = match($boleta->estado_sunat) {
        'ACEPTADO' => 'bg-green-50 text-green-700 border-green-200',
        'RECHAZADO' => 'bg-red-50 text-red-700 border-red-200',
        'ANULADO' => 'bg-gray-100 text-gray-600 border-gray-200',
        default => 'bg-amber-50 text-amber-700 border-amber-200',
    };
    $simbolo = $boleta->moneda === 'USD' ? '$' : 'S/';
    $modal = $modal ?? false;
@endphp
<div class="space-y-5 {{ $modal ? 'p-5' : 'max-w-4xl' }}">
    <div class="flex items-center justify-between">
        <div>
            @unless($modal)
                <a href="{{ route('empresa.boletas.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver a boletas</a>
            @endunless
            <h1 class="text-xl font-bold text-gray-800 {{ $modal ? '' : 'mt-1 text-2xl' }}">Boleta {{ $boleta->numero_completo }}</h1>
        </div>
        <span class="rounded-full border px-3 py-1 text-sm font-medium {{ $estadoClass }}">{{ $boleta->estado_sunat ?? 'PENDIENTE' }}</span>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap gap-2">
        @if($boleta->pdf_path || $boleta->estado_sunat === 'ACEPTADO')
            <a href="{{ route('empresa.documents.download', ['boleta', $boleta->id, 'pdf']) }}" class="rounded-md bg-rose-600 text-white px-4 py-2 text-sm font-medium hover:bg-rose-700">Descargar PDF</a>
        @endif
        @if($boleta->xml_path)
            <a href="{{ route('empresa.documents.download', ['boleta', $boleta->id, 'xml']) }}" class="rounded-md bg-gray-100 text-gray-700 px-4 py-2 text-sm font-medium hover:bg-gray-200">XML</a>
        @endif
        @if($boleta->cdr_path)
            <a href="{{ route('empresa.documents.download', ['boleta', $boleta->id, 'cdr']) }}" class="rounded-md bg-gray-100 text-gray-700 px-4 py-2 text-sm font-medium hover:bg-gray-200">CDR</a>
        @endif
        @if($boleta->estado_sunat !== 'ACEPTADO')
            <form method="POST" action="{{ route('empresa.boletas.send-sunat', $boleta->id) }}" x-data="{ enviando: false }" @submit="enviando = true">
                @csrf
                <button type="submit" :disabled="enviando"
                        class="rounded-md bg-blue-600 text-white px-4 py-2 text-sm font-medium hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-text="enviando ? 'Enviando…' : 'Reenviar a SUNAT'"></span>
                </button>
            </form>
        @endif
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Cliente</h2>
            <p class="text-sm text-gray-900 font-medium">{{ $boleta->client?->razon_social }}</p>
            <p class="text-sm text-gray-600">{{ $boleta->client?->numero_documento }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ $boleta->client?->direccion }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <h2 class="font-semibold text-gray-800 mb-3">Datos del comprobante</h2>
            <dl class="text-sm space-y-1">
                <div class="flex justify-between"><dt class="text-gray-500">Emisión</dt><dd class="text-gray-800">{{ $boleta->fecha_emision?->format('d/m/Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Moneda</dt><dd class="text-gray-800">{{ $boleta->moneda }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Descripción</th>
                    <th class="px-4 py-3 text-right">Cant.</th>
                    <th class="px-4 py-3 text-right">V. Unit.</th>
                    <th class="px-4 py-3 text-right">Importe</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach(($boleta->detalles ?? []) as $d)
                    <tr>
                        <td class="px-4 py-3 text-gray-800">{{ $d['descripcion'] ?? '' }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ $d['cantidad'] ?? '' }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format((float) ($d['mto_valor_unitario'] ?? 0), 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-800">{{ number_format((float) ($d['cantidad'] ?? 0) * (float) ($d['mto_valor_unitario'] ?? 0), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="flex justify-end p-4 bg-gray-50">
            <div class="w-full max-w-xs space-y-1 text-sm">
                <div class="flex justify-between text-gray-600"><span>Op. gravadas</span><span>{{ $simbolo }} {{ number_format((float) $boleta->mto_oper_gravadas, 2) }}</span></div>
                <div class="flex justify-between text-gray-600"><span>IGV</span><span>{{ $simbolo }} {{ number_format((float) $boleta->mto_igv, 2) }}</span></div>
                <div class="flex justify-between font-bold text-gray-900 text-base border-t border-gray-200 pt-1"><span>Total</span><span>{{ $simbolo }} {{ number_format((float) $boleta->mto_imp_venta, 2) }}</span></div>
            </div>
        </div>
    </div>
</div>
