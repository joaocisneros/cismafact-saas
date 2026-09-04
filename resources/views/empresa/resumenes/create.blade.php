@extends('layouts.app')

@section('title', 'Anular boletas')

@section('content')
<div class="space-y-5 max-w-4xl">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Anular boletas</h1>
            <p class="text-gray-500 mt-1">Elige la fecha y las boletas a anular. Se envían a SUNAT en un resumen diario.</p>
        </div>
        <a href="{{ route('empresa.resumenes.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← Volver</a>
    </div>

    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3">
            <ul class="list-disc list-inside text-sm space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @if(session('error'))<div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ session('error') }}</div>@endif

    <form method="GET" action="{{ route('empresa.resumenes.create') }}" class="bg-white border border-gray-200 rounded-lg p-5 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de emisión de las boletas</label>
            <input type="date" name="fecha" value="{{ $fecha ?? now()->toDateString() }}" max="{{ now()->toDateString() }}"
                   class="rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>
        <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white">Buscar boletas</button>
    </form>

    @if($fecha)
        @if($boletas->isEmpty())
            <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
                No hay boletas ACEPTADAS para el {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}.
            </div>
        @else
        {{-- Se bloquea al enviar: el resumen va a SUNAT y gasta correlativo, asi
             que un segundo clic durante la espera manda otro. --}}
        <form method="POST" action="{{ route('empresa.resumenes.store') }}"
              x-data="{ enviando: false }" @submit="enviando = true"
              class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
            @csrf
            <input type="hidden" name="fecha_resumen" value="{{ $fecha }}">

            <div>
                <h2 class="font-semibold text-gray-800 mb-2">Boletas del {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}</h2>
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase text-gray-500">
                        <tr><th class="py-2 pr-2 w-10"></th><th class="py-2 pr-2">Boleta</th><th class="py-2 pr-2 text-right">Monto</th></tr>
                    </thead>
                    <tbody>
                        @foreach($boletas as $b)
                            <tr class="border-t border-gray-100">
                                <td class="py-2 pr-2"><input type="checkbox" name="boletas[]" value="{{ $b->id }}" checked></td>
                                <td class="py-2 pr-2 font-medium text-gray-900">{{ $b->numero_completo }}</td>
                                <td class="py-2 pr-2 text-right text-gray-700">S/ {{ number_format($b->mto_imp_venta, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-700">
                ⚠️ Las boletas seleccionadas se enviarán a SUNAT con estado <strong>anulado</strong>. Esta acción se comunica oficialmente a SUNAT.
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('empresa.resumenes.index') }}" class="rounded-md border border-gray-300 px-5 py-2.5 text-sm">Cancelar</a>
                <button type="submit" :disabled="enviando"
                        class="rounded-md bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-text="enviando ? 'Enviando a SUNAT…' : 'Anular y enviar a SUNAT'"></span>
                </button>
            </div>
        </form>
        @endif
    @endif
</div>
@endsection
