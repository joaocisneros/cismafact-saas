@extends('layouts.app')

@section('title', 'Detalle Documento')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('super-admin.documents') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Detalle de Documento</h1>
            @php
                $tipoLabels = ['factura' => 'Factura', 'boleta' => 'Boleta', 'nc' => 'NC', 'nd' => 'ND', 'guia' => 'Guia'];
                $tipoColors = ['factura' => 'blue', 'boleta' => 'green', 'nc' => 'yellow', 'nd' => 'red', 'guia' => 'violet'];
            @endphp
            <span class="px-3 py-1 bg-{{ $tipoColors[$type] ?? 'gray' }}-100 text-{{ $tipoColors[$type] ?? 'gray' }}-700 rounded text-sm font-medium">
                {{ $tipoLabels[$type] ?? $type }}
            </span>
            <x-status-badge :status="$doc->estado_sunat" />
        </div>
        <div class="flex gap-2">
            <a href="{{ route('super-admin.documents.download', ['type' => $type, 'id' => $doc->id, 'file' => 'xml']) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Descargar XML</a>
            <a href="{{ route('super-admin.documents.download', ['type' => $type, 'id' => $doc->id, 'file' => 'cdr']) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Descargar CDR</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-4">Información del Documento</h3>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Número:</dt>
                    <dd class="text-sm font-medium">{{ $doc->numero_completo ?? ($doc->serie . '-' . $doc->correlativo) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Empresa:</dt>
                    <dd class="text-sm font-medium">{{ $doc->company->razon_social ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">RUC:</dt>
                    <dd class="text-sm font-medium">{{ $doc->company->ruc ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Fecha Emisión:</dt>
                    <dd class="text-sm font-medium">{{ $doc->fecha_emision ?? $doc->created_at->format('d/m/Y') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Estado SUNAT:</dt>
                    <dd class="text-sm font-medium"><x-status-badge :status="$doc->estado_sunat" /></dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-4">Montos</h3>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Subtotal:</dt>
                    <dd class="text-sm font-medium">S/ {{ number_format($doc->mto_op_gravadas ?? $doc->subtotal ?? 0, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">IGV:</dt>
                    <dd class="text-sm font-medium">S/ {{ number_format($doc->mto_igv ?? 0, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Total:</dt>
                    <dd class="text-lg font-bold text-green-600">S/ {{ number_format($doc->mto_imp_venta ?? $doc->total ?? 0, 2) }}</dd>
                </div>
            </dl>
        </div>
    </div>

    @if(isset($doc->items) && count($doc->items) > 0)
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-md font-semibold text-gray-800 mb-4">Ítems</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500">
                        <th class="pb-3 font-medium">#</th>
                        <th class="pb-3 font-medium">Descripción</th>
                        <th class="pb-3 font-medium text-center">Cantidad</th>
                        <th class="pb-3 font-medium text-right">P. Unitario</th>
                        <th class="pb-3 font-medium text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($doc->items as $i => $item)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3">{{ $i + 1 }}</td>
                        <td class="py-3">{{ $item->descripcion ?? $item->nombre_item ?? '-' }}</td>
                        <td class="py-3 text-center">{{ $item->cantidad ?? '-' }}</td>
                        <td class="py-3 text-right">S/ {{ number_format($item->mto_valor_unitario ?? $item->precio_unitario ?? 0, 2) }}</td>
                        <td class="py-3 text-right font-medium">S/ {{ number_format($item->mto_venta ?? $item->total ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
