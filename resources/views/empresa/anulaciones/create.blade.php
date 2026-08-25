@extends('layouts.app')

@section('title', 'Anular comprobantes')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Anular comprobantes</h1>
            <p class="mt-1 text-gray-500">
                Elige la fecha en que se emitieron y marca cuáles quieres anular ante SUNAT.
            </p>
        </div>
        <a href="{{ route('empresa.anulaciones.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver</a>
    </div>

    <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
        Puedes anular <strong>facturas, boletas y notas</strong> desde aquí. SUNAT usa un trámite distinto para
        cada tipo, pero de eso se encarga el sistema: tú solo marca lo que quieres anular.
        El plazo es de <strong>{{ $diasDePlazo }} días</strong> desde la emisión; para algo más antiguo, emite una Nota de Crédito.
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">
            <ul class="list-inside list-disc space-y-0.5 text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Paso 1: la fecha --}}
    <form method="GET" action="{{ route('empresa.anulaciones.create') }}"
          class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-5">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Fecha en que se emitieron</label>
            <input type="date" name="fecha" value="{{ $fecha ?? now()->toDateString() }}" max="{{ now()->toDateString() }}"
                   class="rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>
        <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white">Buscar comprobantes</button>
    </form>

    {{-- Paso 2: elegir --}}
    @if($fecha)
        @if(count($documentos) === 0)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                No hay comprobantes aceptados por SUNAT con fecha
                {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}, o ya fueron anulados.
            </div>
        @else
            <form method="POST" action="{{ route('empresa.anulaciones.store') }}"
                  class="space-y-4 rounded-lg border border-gray-200 bg-white p-5">
                @csrf
                <input type="hidden" name="fecha_referencia" value="{{ $fecha }}">

                <div>
                    <h2 class="mb-3 font-semibold text-gray-800">
                        Comprobantes del {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}
                    </h2>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs font-semibold uppercase text-gray-500">
                                <tr>
                                    <th class="w-10 py-2 pr-2"></th>
                                    <th class="py-2 pr-2">Comprobante</th>
                                    <th class="py-2 pr-2">Tipo</th>
                                    <th class="py-2 pr-2">Sucursal</th>
                                    <th class="py-2 pr-2">Trámite</th>
                                    <th class="py-2 pr-2 text-right">Monto</th>
                                </tr>
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
                                        <td class="py-2 pr-2 text-gray-600">{{ $d['sucursal'] ?? '—' }}</td>
                                        <td class="py-2 pr-2">
                                            <span class="rounded px-2 py-0.5 text-xs {{ $d['via'] === 'Resumen de boletas' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700' }}">
                                                {{ $d['via'] }}
                                            </span>
                                        </td>
                                        <td class="py-2 pr-2 text-right text-gray-700">S/ {{ number_format($d['monto'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="mt-2 text-xs text-gray-500">
                        La columna «Trámite» es informativa: SUNAT anula las boletas por resumen y el resto por
                        comunicación de baja. El sistema envía cada grupo por donde corresponde.
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Motivo de la anulación</label>
                    <input name="motivo" value="{{ old('motivo') }}" required minlength="3" maxlength="250"
                           placeholder="Ej.: error en los datos del cliente"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-gray-500">Se envía a SUNAT junto con la anulación.</p>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('empresa.anulaciones.index') }}"
                       class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700">Cancelar</a>
                    <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                            onclick="return confirm('¿Anular los comprobantes marcados? Esta acción se comunica a SUNAT y no se puede deshacer.')">
                        Anular en SUNAT
                    </button>
                </div>
            </form>
        @endif
    @endif
</div>
@endsection
