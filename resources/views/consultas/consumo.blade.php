@extends('layouts.consultas')

@section('title', 'Consumo')

@section('content')
<div class="mb-5">
    <h1 class="text-xl font-semibold text-gray-900">Consumo</h1>
    <p class="text-sm text-gray-600">Cuánto llevas gastado y cómo ha ido en los últimos meses.</p>
</div>

<div class="grid gap-4 lg:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">Este mes</h2>
            <span class="text-xs capitalize text-gray-400">{{ now()->translatedFormat('F') }}</span>
        </div>
        <div class="flex flex-wrap justify-center gap-6 p-5">
            @forelse($consumo as $servicio)
                @include('consultas._anillo', ['servicio' => $servicio])
            @empty
                <p class="py-4 text-sm text-gray-500">Todavía no hay consumo que mostrar.</p>
            @endforelse
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">Los últimos seis meses</h2>
        </div>
        <div class="p-5">
            @php($tope = max(1, collect($meses)->max('total')))
            <div class="flex h-28 items-end gap-2">
                @foreach($meses as $mes)
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <span class="text-[11px] tabular-nums text-gray-500">{{ $mes['total'] }}</span>
                        {{-- Alto proporcional al mayor del periodo, con un mínimo
                             visible: una barra de cero píxeles no se distingue de
                             un mes que falta. --}}
                        <div class="w-full rounded-t {{ $mes['total'] > 0 ? 'bg-blue-600' : 'bg-gray-200' }}"
                             style="height: {{ $mes['total'] > 0 ? max(6, round($mes['total'] / $tope * 84)) : 4 }}px"></div>
                        <span class="text-[11px] capitalize text-gray-400">{{ $mes['etiqueta'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="mt-4 rounded-xl bg-blue-50 px-4 py-3 text-sm text-gray-700">
    <p class="mb-1"><strong class="text-gray-900">Las consultas sin ficha no gastan cuota.</strong>
       Si preguntas por un DNI que no existe en RENIEC, no se te descuenta.</p>
    <p>Tampoco gastan las que se responden con datos de una consulta tuya anterior.</p>
</div>
@endsection
