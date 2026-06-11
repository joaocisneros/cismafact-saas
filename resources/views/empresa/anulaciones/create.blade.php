@extends('layouts.app')

@section('title', 'Nueva anulación')

@section('content')
<div class="space-y-5 max-w-4xl">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Nueva anulación</h1>
            <p class="text-gray-500 mt-1">Selecciona la fecha y los comprobantes a dar de baja en SUNAT.</p>
        </div>
        <a href="{{ route('empresa.anulaciones.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver</a>
    </div>

    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3">
            <ul class="list-disc list-inside text-sm space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @if(session('error'))<div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ session('error') }}</div>@endif

    {{-- Paso 1: elegir fecha --}}
    <form method="GET" action="{{ route('empresa.anulaciones.create') }}" class="bg-white border border-gray-200 rounded-lg p-5 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de emisión de los comprobantes</label>
            <input type="date" name="fecha" value="{{ $fecha ?? now()->toDateString() }}" max="{{ now()->toDateString() }}"
                   class="rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>
        <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white">Buscar comprobantes</button>
    </form>

    {{-- Paso 2: seleccionar comprobantes --}}
    @if($fecha)
        @if(count($documentos) === 0)
            <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
                No hay facturas ni notas ACEPTADAS para el {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}.
            </div>
        @else
        <form method="POST" action="{{ route('empresa.anulaciones.store') }}" class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
            @csrf
            <input type="hidden" name="fecha_referencia" value="{{ $fecha }}">

            <div>
                <h2 class="font-semibold text-gray-800 mb-2">Comprobantes del {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}</h2>
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase text-gray-500">
                        <tr><th class="py-2 pr-2 w-10"></th><th class="py-2 pr-2">Comprobante</th><th class="py-2 pr-2">Tipo</th><th class="py-2 pr-2 text-right">Monto</th></tr>
                    </thead>
                    <tbody>
                        @foreach($documentos as $i => $d)
                            <tr class="border-t border-gray-100">
                                <td class="py-2 pr-2">
                                    <input type="checkbox" checked
                                           name="documentos[{{ $i }}][_sel]" value="1"
                                           onchange="this.closest('tr').querySelectorAll('input[type=hidden]').forEach(h=>h.disabled=!this.checked)">
                                    <input type="hidden" name="documentos[{{ $i }}][tipo_documento]" value="{{ $d['tipo_documento'] }}">
                                    <input type="hidden" name="documentos[{{ $i }}][serie]" value="{{ $d['serie'] }}">
                                    <input type="hidden" name="documentos[{{ $i }}][correlativo]" value="{{ $d['correlativo'] }}">
                                </td>
                                <td class="py-2 pr-2 font-medium text-gray-900">{{ $d['numero_completo'] }}</td>
                                <td class="py-2 pr-2 text-gray-600">{{ $d['tipo_nombre'] }}</td>
                                <td class="py-2 pr-2 text-right text-gray-700">S/ {{ number_format($d['monto'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Motivo de la anulación</label>
                <input type="text" name="motivo" value="{{ old('motivo', 'Anulación de la operación') }}" required minlength="3" maxlength="250"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Ej: Error en los datos del cliente">
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('empresa.anulaciones.index') }}" class="rounded-md border border-gray-300 px-5 py-2.5 text-sm">Cancelar</a>
                <button class="rounded-md bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700">Anular y enviar a SUNAT</button>
            </div>
        </form>
        @endif
    @endif
</div>
@endsection
