@php
    $estadoClass = match($documento->estado_sunat) {
        'ACEPTADO' => 'bg-green-50 text-green-700 border-green-200',
        'RECHAZADO' => 'bg-red-50 text-red-700 border-red-200',
        'ANULADO' => 'bg-gray-100 text-gray-600 border-gray-200',
        default => 'bg-amber-50 text-amber-700 border-amber-200',
    };
    $modal = $modal ?? false;
    $puedeVerPdf = $documento->pdf_path || $documento->estado_sunat === 'ACEPTADO';
@endphp

<div class="{{ $modal ? '' : 'mx-auto max-w-6xl space-y-4' }}" x-data="{ formatoPdf: 'A4' }">
    @unless($modal)
        <div>
            <a href="{{ route($rutaIndice) }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; Volver a {{ strtolower($tipoPlural) }}</a>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $tipoNombre }} {{ $documento->numero_completo }}</h1>
        </div>
    @endunless

    <div class="border-b border-gray-200 bg-white px-5 py-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 2h9l5 5v15H6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M9 13h6M9 17h6"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900">{{ $tipoNombre }} {{ $documento->numero_completo }}</p>
                    <p class="truncate text-xs text-gray-500">Emitida el {{ $documento->fecha_emision?->format('d/m/Y') }} · {{ $documento->client?->razon_social ?? 'Cliente no registrado' }}</p>
                </div>
                <span class="shrink-0 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $estadoClass }}">{{ $documento->estado_sunat ?? 'PENDIENTE' }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:flex-nowrap">
                @if($documento->xml_path)
                    <a href="{{ route('empresa.documents.download', [$tipoRuta, $documento->id, 'xml']) }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">XML</a>
                @endif
                @if($documento->cdr_path)
                    <a href="{{ route('empresa.documents.download', [$tipoRuta, $documento->id, 'cdr']) }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">CDR</a>
                @endif
                @if($puedeVerPdf)
                    @if(in_array($tipoRuta, ['factura', 'boleta'], true))
                        <label class="shrink-0">
                            <span class="sr-only">Formato del PDF</span>
                            <select x-model="formatoPdf" aria-label="Formato del PDF" title="Formato del PDF" class="w-40 rounded-lg border-gray-300 py-2 pl-3 pr-8 text-sm text-gray-700 focus:border-blue-500 focus:ring-blue-500">
                                <option value="A4">A4</option>
                                <option value="A5">A5 compacto</option>
                                <option value="80mm">Ticket 80 mm</option>
                                <option value="58mm">Ticket 58 mm</option>
                            </select>
                        </label>
                    @endif
                    <a :href="'{{ route('empresa.documents.download', [$tipoRuta, $documento->id, 'pdf']) }}?format=' + formatoPdf" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14"/></svg>
                        Descargar PDF
                    </a>
                @endif
                @if($documento->estado_sunat !== 'ACEPTADO')
                    <form method="POST" action="{{ route($rutaSunat, $documento->id) }}" x-data="{ enviando: false }" @submit="enviando = true">
                        @csrf
                        <button type="submit" :disabled="enviando" class="rounded-lg bg-gray-900 px-3.5 py-2 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-60">
                            <span x-text="enviando ? 'Enviando…' : 'Reenviar a SUNAT'"></span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if($puedeVerPdf)
        <div class="bg-gray-100 p-3 sm:p-5">
            <div class="overflow-hidden rounded-lg border border-gray-300 bg-white shadow-sm">
                <iframe
                    :src="'{{ route('empresa.documents.view', [$tipoRuta, $documento->id, 'pdf'], false) }}?format=' + formatoPdf + '#toolbar=0&navpanes=0&view=FitH'"
                    title="Vista previa de {{ strtolower($tipoNombre) }} {{ $documento->numero_completo }}"
                    class="block w-full bg-gray-200"
                    style="height: min(68vh, 760px); min-height: 520px;"
                ></iframe>
            </div>
        </div>
    @else
        <div class="flex min-h-72 flex-col items-center justify-center bg-gray-50 px-6 py-12 text-center">
            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.7 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/></svg>
            </div>
            <p class="font-semibold text-gray-900">La vista previa aún no está disponible</p>
            <p class="mt-1 max-w-md text-sm text-gray-500">El PDF aparecerá aquí cuando el comprobante haya sido procesado.</p>
        </div>
    @endif
</div>
