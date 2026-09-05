@extends('layouts.consultas')

@section('title', 'Panel')

@section('content')
@php($produccion = $llaves->firstWhere('entorno', 'produccion') ?? $llaves->first())

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Panel</h1>
        <p class="text-[15px] text-gray-600">Tu servicio de consultas, de un vistazo.</p>
    </div>
    @if($produccion?->expira_en)
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
            Activa hasta el {{ $produccion->expira_en->format('d/m/Y') }}
        </span>
    @endif
</div>

@if($llaves->isEmpty())
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-8 text-center">
        <p class="font-semibold text-amber-900">Todavía no tienes ninguna llave asignada.</p>
        <p class="mt-1 text-sm text-amber-800">Escríbenos y te la damos de alta.</p>
    </div>
@else
    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Consultas del mes</p>
            <p class="mt-1 text-4xl font-semibold tabular-nums text-gray-900">{{ number_format($gastadas) }}</p>
            <p class="mt-0.5 text-sm text-gray-500">de {{ number_format($disponibles) }} disponibles</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Te quedan</p>
            <p class="mt-1 text-4xl font-semibold tabular-nums text-gray-900">{{ number_format(max(0, $disponibles - $gastadas)) }}</p>
            <p class="mt-0.5 text-sm text-gray-500">hasta el día 1</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Tu plan</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $produccion?->plan?->nombre ?? '—' }}</p>
            <p class="mt-0.5 text-sm text-gray-500">{{ collect($produccion?->servicios ?? [])->map(fn($s) => strtoupper($s))->join(' y ') }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Tus llaves</p>
            <p class="mt-1 text-4xl font-semibold tabular-nums text-gray-900">{{ $llaves->count() }}</p>
            <p class="mt-0.5 text-sm text-gray-500">{{ $llaves->where('entorno', 'produccion')->count() }} en producción</p>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3.5">
                <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400">
                    Consumo de {{ now()->translatedFormat('F') }}
                </h2>
                <span class="text-sm text-gray-400">Se reinicia el día 1</span>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-10 p-8">
                @foreach($consumo as $servicio)
                    @include('consultas._anillo', ['servicio' => $servicio])
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white xl:col-span-2">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3.5">
                <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400">Últimas consultas</h2>
                <a href="{{ route('consultas.consultas') }}" class="text-sm font-semibold text-blue-700 hover:underline">Ver todas</a>
            </div>
            @include('consultas._tabla-consultas', ['filas' => $ultimas])
        </div>
    </div>
@endif
@endsection
