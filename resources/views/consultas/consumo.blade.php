@extends('layouts.consultas')

@section('title', 'Consumo')

@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-semibold text-gray-900">Consumo</h1>
    <p class="text-[15px] text-gray-600">Cuánto llevas gastado y cómo ha ido en los últimos meses.</p>
</div>

<div class="grid gap-4 lg:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3.5">
            <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400">Este mes</h2>
            <span class="text-sm capitalize text-gray-400">{{ now()->translatedFormat('F') }}</span>
        </div>
        <div class="flex flex-wrap items-center justify-center gap-10 p-8">
            @forelse($consumo as $servicio)
                @include('consultas._anillo', ['servicio' => $servicio])
            @empty
                <p class="py-4 text-sm text-gray-500">Todavía no hay consumo que mostrar.</p>
            @endforelse
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-3.5">
            <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400">Los últimos seis meses</h2>
        </div>
        <div class="p-6">
            @php($tope = max(1, collect($meses)->max('total')))
            <div class="flex h-56 items-end gap-3">
                @foreach($meses as $mes)
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <span class="text-sm font-medium tabular-nums text-gray-600">{{ $mes['total'] }}</span>
                        {{-- Alto proporcional al mayor del periodo, con un mínimo
                             visible: una barra de cero píxeles no se distingue de
                             un mes que falta. --}}
                        <div class="w-full rounded-t {{ $mes['total'] > 0 ? 'bg-blue-600' : 'bg-gray-200' }}"
                             style="height: {{ $mes['total'] > 0 ? max(10, round($mes['total'] / $tope * 180)) : 6 }}px"></div>
                        <span class="text-sm capitalize text-gray-500">{{ $mes['etiqueta'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="mt-4 rounded-xl bg-blue-50 px-5 py-4 text-[15px] text-gray-700">
    <p class="mb-1"><strong class="text-gray-900">Las consultas sin ficha no gastan cuota.</strong>
       Si preguntas por un DNI que no existe en RENIEC, no se te descuenta.</p>
    <p>Tampoco gastan las que se responden con datos de una consulta tuya anterior.</p>
</div>
@endsection
