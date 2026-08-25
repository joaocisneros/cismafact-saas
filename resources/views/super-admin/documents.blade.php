@extends('layouts.app')

@section('title', 'Documentos Emitidos')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Documentos Emitidos</h1>
        <p class="mt-1 text-gray-500">Consulta global optimizada de documentos electrónicos.</p>
    </div>

    <form method="GET" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-6">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Buscar</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Serie/número..."
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Empresa</label>
                <select name="company_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Todas</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>{{ $company->razon_social }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Tipo</label>
                <select name="type" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Todos</option>
                    <option value="factura" {{ request('type') == 'factura' ? 'selected' : '' }}>Factura</option>
                    <option value="boleta" {{ request('type') == 'boleta' ? 'selected' : '' }}>Boleta</option>
                    <option value="nc" {{ request('type') == 'nc' ? 'selected' : '' }}>Nota de Crédito</option>
                    <option value="nd" {{ request('type') == 'nd' ? 'selected' : '' }}>Nota de Débito</option>
                    <option value="guia" {{ request('type') == 'guia' ? 'selected' : '' }}>Guía de Remisión</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Estado SUNAT</label>
                <select name="status" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Todos</option>
                    <option value="PENDIENTE" {{ request('status') == 'PENDIENTE' ? 'selected' : '' }}>Pendiente</option>
                    <option value="PROCESANDO" {{ request('status') == 'PROCESANDO' ? 'selected' : '' }}>Procesando</option>
                    <option value="ENVIADO" {{ request('status') == 'ENVIADO' ? 'selected' : '' }}>Enviado</option>
                    <option value="ACEPTADO" {{ request('status') == 'ACEPTADO' ? 'selected' : '' }}>Aceptado</option>
                    <option value="RECHAZADO" {{ request('status') == 'RECHAZADO' ? 'selected' : '' }}>Rechazado</option>
                    <option value="ERROR" {{ request('status') == 'ERROR' ? 'selected' : '' }}>Error</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Desde</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Hasta</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>
        </div>
        <div class="mt-3 flex gap-2">
            <button type="submit" class="min-w-24 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Buscar</button>
            <a href="{{ route('super-admin.documents') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Limpiar</a>
        </div>
    </form>

    <div class="rounded-xl bg-white p-6 shadow-sm">
        @if($documents->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="pb-3 font-medium">Tipo</th>
                            <th class="pb-3 font-medium">Documento</th>
                            <th class="pb-3 font-medium">Empresa</th>
                            <th class="pb-3 font-medium">Total</th>
                            <th class="pb-3 font-medium">Fecha</th>
                            <th class="pb-3 font-medium">Estado</th>
                            <th class="pb-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documents as $doc)
                        @php
                            $colors = [
                                'factura' => 'bg-blue-100 text-blue-700',
                                'boleta' => 'bg-green-100 text-green-700',
                                'nc' => 'bg-amber-100 text-amber-700',
                                'nd' => 'bg-red-100 text-red-700',
                                'guia' => 'bg-violet-100 text-violet-700',
                            ];
                            $documentNumber = $doc->numero_completo ?: trim($doc->serie . '-' . $doc->correlativo, '-');
                        @endphp
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3">
                                <span class="rounded px-2 py-1 text-xs {{ $colors[$doc->type_key] ?? 'bg-gray-100 text-gray-700' }}">{{ $doc->type_label }}</span>
                            </td>
                            <td class="py-3 font-medium">{{ $documentNumber }}</td>
                            <td class="py-3 text-gray-500">{{ $doc->empresa ?? 'N/A' }}</td>
                            <td class="py-3 text-gray-500">S/ {{ number_format((float) $doc->total, 2) }}</td>
                            <td class="py-3 text-gray-500">{{ \Illuminate\Support\Carbon::parse($doc->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="py-3"><x-status-badge :status="$doc->estado_sunat" /></td>
                            <td class="py-3">
                                {{-- PDF/XML/CDR se quedan como texto a proposito: son tres
                                     formatos distintos y tres iconos de descarga iguales no
                                     dirian cual es cual. --}}
                                <div class="flex items-center gap-1.5">
                                    <x-icon-action icon="ver" label="Ver detalle" color="blue" type="button"
                                                   onclick="window.openAdminModal('{{ route('super-admin.documents.show', ['type' => $doc->type_key, 'id' => $doc->id]) }}', 'Detalle del documento')" />
                                    {{-- El PDF no se condiciona a pdf_path: los comprobantes
                                         emitidos por API no lo guardan al crearse y la ruta lo
                                         genera al vuelo. Antes el boton simplemente no salia. --}}
                                    <a href="{{ route('super-admin.documents.view', ['type' => $doc->type_key, 'id' => $doc->id, 'file' => 'pdf']) }}" target="_blank"
                                       title="Representación impresa del comprobante"
                                       class="rounded-md bg-red-50 px-2 py-1 text-xs font-semibold text-red-600 ring-1 ring-inset ring-red-200 transition hover:bg-red-100">PDF</a>
                                    @if($doc->xml_path)
                                        <a href="{{ route('super-admin.documents.download', ['type' => $doc->type_key, 'id' => $doc->id, 'file' => 'xml']) }}"
                                           title="Documento enviado a SUNAT"
                                           class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-200">XML</a>
                                    @endif
                                    @if($doc->cdr_path)
                                        <a href="{{ route('super-admin.documents.download', ['type' => $doc->type_key, 'id' => $doc->id, 'file' => 'cdr']) }}"
                                           title="Respuesta oficial de SUNAT"
                                           class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-600 ring-1 ring-inset ring-emerald-200 transition hover:bg-emerald-100">CDR</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 border-t pt-3">
                {{ $documents->links() }}
            </div>
        @else
            <p class="py-4 text-center text-sm text-gray-500">No se encontraron documentos.</p>
        @endif
    </div>
</div>
@endsection
