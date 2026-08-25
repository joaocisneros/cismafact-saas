@extends('layouts.app')

@section('title', 'Reporte para el contador')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-gray-800">Reporte para el contador</h2>
        <p class="text-sm text-gray-500 mt-1">
            Todas las ventas del mes en un archivo: facturas, boletas y notas de crédito y débito.
        </p>
    </div>

    <form method="GET" action="{{ route('empresa.reportes.contador') }}"
          class="bg-white rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label for="periodo" class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                <select name="periodo" id="periodo" onchange="this.form.submit()"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    @foreach($meses as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected($periodo === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="incluir_no_aceptados" value="1"
                           onchange="this.form.submit()" @checked(! $soloAceptados)
                           class="rounded border-gray-300">
                    Incluir también los pendientes y rechazados
                </label>
                <p class="text-xs text-gray-500 mt-1">
                    Normalmente déjalo sin marcar: un comprobante rechazado por SUNAT no se declara.
                </p>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <p class="text-xs text-gray-500">Comprobantes</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($totales['documentos']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <p class="text-xs text-gray-500">Base imponible</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">S/ {{ number_format($totales['gravadas'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <p class="text-xs text-gray-500">IGV</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">S/ {{ number_format($totales['igv'], 2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <p class="text-xs text-gray-500">Total</p>
            <p class="mt-1 text-2xl font-semibold text-blue-700">S/ {{ number_format($totales['total'], 2) }}</p>
        </div>
    </div>

    @if($totales['documentos'] === 0)
        <div class="rounded-xl border border-gray-200 bg-white p-6 text-center">
            <p class="text-sm text-gray-500">No hay comprobantes en este periodo.</p>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-1">Descargar</h3>
            <p class="text-xs text-gray-500 mb-4">
                Archivo CSV que se abre en Excel. Incluye cliente, base imponible, IGV, total y moneda de cada comprobante.
            </p>

            <a href="{{ route('empresa.reportes.contador.descargar', request()->only('periodo', 'incluir_no_aceptados')) }}"
               class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2.25a.75.75 0 01.75.75v11.19l3.22-3.22a.75.75 0 111.06 1.06l-4.5 4.5a.75.75 0 01-1.06 0l-4.5-4.5a.75.75 0 111.06-1.06l3.22 3.22V3a.75.75 0 01.75-.75zM3 16.5a.75.75 0 01.75.75v2.25c0 .414.336.75.75.75h15a.75.75 0 00.75-.75V17.25a.75.75 0 011.5 0v2.25a2.25 2.25 0 01-2.25 2.25h-15A2.25 2.25 0 012.25 19.5v-2.25A.75.75 0 013 16.5z"/>
                </svg>
                Descargar CSV
            </a>
        </div>
    @endif

    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
        <p class="text-sm text-blue-900">
            <strong>Ojo:</strong> este archivo trae los comprobantes emitidos desde esta plataforma.
        </p>
    </div>
</div>
@endsection
