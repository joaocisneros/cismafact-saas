@php
    $tipoLabels = [
        'factura' => 'Factura',
        'boleta' => 'Boleta',
        'nc' => 'Nota de crédito',
        'nd' => 'Nota de débito',
        'guia' => 'Guía de remisión',
    ];
    $items = $doc->items ?? $doc->detalles ?? [];
@endphp

<div class="space-y-5 p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase text-blue-600">{{ $tipoLabels[$type] ?? $type }}</p>
            <h4 class="text-lg font-semibold text-gray-900">{{ $doc->numero_completo ?? ($doc->serie . '-' . $doc->correlativo) }}</h4>
            <p class="text-sm text-gray-500">{{ $doc->company->razon_social ?? 'N/A' }}</p>
        </div>
        <x-status-badge :status="$doc->estado_sunat" />
    </div>

    <dl class="grid gap-3 rounded-md bg-gray-50 p-4 text-sm md:grid-cols-4">
        <div><dt class="text-gray-500">RUC</dt><dd class="font-medium">{{ $doc->company->ruc ?? 'N/A' }}</dd></div>
        <div><dt class="text-gray-500">Emisión</dt><dd class="font-medium">{{ $doc->fecha_emision ? \Illuminate\Support\Carbon::parse($doc->fecha_emision)->format('d/m/Y') : optional($doc->created_at)->format('d/m/Y') }}</dd></div>
        <div><dt class="text-gray-500">Total</dt><dd class="font-semibold text-green-700">S/ {{ number_format((float) ($doc->mto_imp_venta ?? $doc->total ?? 0), 2) }}</dd></div>
        <div><dt class="text-gray-500">Última consulta</dt><dd class="font-medium">{{ $doc->consulta_cpe_fecha ? \Illuminate\Support\Carbon::parse($doc->consulta_cpe_fecha)->format('d/m/Y H:i') : 'Sin consulta' }}</dd></div>
    </dl>

    <div class="flex flex-wrap gap-2">
        @if($type !== 'guia')
            <form method="POST" action="{{ route('super-admin.documents.consult', ['type' => $type, 'id' => $doc->id]) }}"
                  data-success-message="Estado SUNAT consultado correctamente.">
                @csrf
                <button class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Consultar SUNAT
                </button>
            </form>
        @endif

        @foreach(['pdf' => 'PDF', 'xml' => 'XML', 'cdr' => 'CDR'] as $file => $label)
            @if($file === 'pdf' || ($doc->{$file . '_path'} ?? false))
                <a href="{{ $file === 'pdf'
                    ? route('super-admin.documents.view', ['type' => $type, 'id' => $doc->id, 'file' => $file])
                    : route('super-admin.documents.download', ['type' => $type, 'id' => $doc->id, 'file' => $file]) }}"
                   @if($file === 'pdf') target="_blank" @endif
                   class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ $label }}
                </a>
            @endif
        @endforeach
    </div>

    @if($type === 'guia')
        <p class="rounded-md bg-violet-50 px-4 py-3 text-sm text-violet-700">
            Las guías consultan su estado mediante el flujo GRE configurado para la empresa.
        </p>
    @endif

    @if(count($items))
        <div class="overflow-x-auto border-y border-gray-200">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-3 py-2">Descripción</th>
                        <th class="px-3 py-2 text-right">Cantidad</th>
                        <th class="px-3 py-2 text-right">Precio</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach(array_slice(is_array($items) ? $items : $items->toArray(), 0, 20) as $item)
                        @php($item = (object) $item)
                        <tr>
                            <td class="px-3 py-2">{{ $item->descripcion ?? '-' }}</td>
                            <td class="px-3 py-2 text-right">{{ $item->cantidad ?? '-' }}</td>
                            <td class="px-3 py-2 text-right">S/ {{ number_format((float) ($item->mto_valor_unitario ?? $item->precio_unitario ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
