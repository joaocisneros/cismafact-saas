@extends('layouts.app')

@section('title', 'Documentos Emitidos')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Documentos Emitidos</h2>
        <form method="GET" class="flex gap-2 flex-wrap w-full sm:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por serie, número o cliente..."
                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none w-full sm:w-48">
            <select name="type" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="all" {{ $type === 'all' ? 'selected' : '' }}>Todos</option>
                <option value="factura" {{ $type === 'factura' ? 'selected' : '' }}>Facturas</option>
                <option value="boleta" {{ $type === 'boleta' ? 'selected' : '' }}>Boletas</option>
                <option value="nota_credito" {{ $type === 'nota_credito' ? 'selected' : '' }}>Notas Crédito</option>
                <option value="nota_debito" {{ $type === 'nota_debito' ? 'selected' : '' }}>Notas Débito</option>
                <option value="guia_remision" {{ $type === 'guia_remision' ? 'selected' : '' }}>Guías Remisión</option>
            </select>
            <input type="text" name="serie" value="{{ request('serie') }}" placeholder="Serie..."
                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none w-20">
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Todos estados</option>
                <option value="ACEPTADO" {{ request('status') === 'ACEPTADO' ? 'selected' : '' }}>Aceptado</option>
                <option value="RECHAZADO" {{ request('status') === 'RECHAZADO' ? 'selected' : '' }}>Rechazado</option>
                <option value="PENDIENTE" {{ request('status') === 'PENDIENTE' ? 'selected' : '' }}>Pendiente</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Filtrar</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Tipo</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Serie</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Número</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Fecha</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Total</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Estado</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Archivos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 rounded text-xs
                                {{ $doc['type'] === 'factura' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $doc['type'] === 'boleta' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $doc['type'] === 'nota_credito' ? 'bg-purple-100 text-purple-700' : '' }}
                                {{ $doc['type'] === 'nota_debito' ? 'bg-orange-100 text-orange-700' : '' }}
                                {{ $doc['type'] === 'guia_remision' ? 'bg-cyan-100 text-cyan-700' : '' }}">
                                {{ $doc['type_label'] }}
                            </span>
                        </td>
                        <td class="py-3 px-4">{{ $doc['serie'] ?? '-' }}</td>
                        <td class="py-3 px-4 font-medium">{{ $doc['numero_completo'] ?? '-' }}</td>
                        <td class="py-3 px-4 text-gray-500">{{ isset($doc['fecha_emision']) ? \Carbon\Carbon::parse($doc['fecha_emision'])->format('d/m/Y') : '-' }}</td>
                        <td class="py-3 px-4">S/ {{ number_format($doc['mto_imp_venta'] ?? 0, 2) }}</td>
                        <td class="py-3 px-4"><x-status-badge :status="$doc['estado_sunat'] ?? 'PENDIENTE'" /></td>
                        <td class="py-3 px-4">
                            {{-- Un color por formato: en gris los tres se confunden. --}}
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('empresa.documents.view', [$doc['type'], $doc['id'], 'pdf']) }}"
                                   target="_blank" title="Representación impresa del comprobante"
                                   class="rounded-md bg-red-50 px-2 py-1 text-xs font-semibold text-red-600 ring-1 ring-inset ring-red-200 transition hover:bg-red-100">PDF</a>
                                @if(!empty($doc['xml_path']))
                                    <a href="{{ route('empresa.documents.download', [$doc['type'], $doc['id'], 'xml']) }}"
                                       title="Documento enviado a SUNAT"
                                       class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-200">XML</a>
                                @endif
                                @if(!empty($doc['cdr_path']))
                                    <a href="{{ route('empresa.documents.download', [$doc['type'], $doc['id'], 'cdr']) }}"
                                       title="Respuesta oficial de SUNAT"
                                       class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-600 ring-1 ring-inset ring-emerald-200 transition hover:bg-emerald-100">CDR</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500">No se encontraron documentos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
