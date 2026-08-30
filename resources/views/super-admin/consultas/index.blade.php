@extends('layouts.app')

@section('title', 'API RUC y DNI')

@section('content')
@php
    // Las pestañas van por la direccion y no por JavaScript: asi se puede
    // enlazar una en concreto, y al guardar un formulario back() vuelve a la
    // misma en vez de tirar al usuario a la primera.
    $pestanas = [
        'planes' => 'Planes',
        'apis' => 'Mis APIs',
        'consumo' => 'Consumo',
        'keys' => 'API Keys',
        'historial' => 'Historial',
        'webhooks' => 'Webhooks',
        'docs' => 'Documentación',
    ];

    $actual = array_key_exists(request('tab'), $pestanas) ? request('tab') : 'planes';
@endphp

<div class="space-y-5">

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    {{-- Las mismas tarjetas del Dashboard, con el mismo componente: si aqui se
         dibujaran a mano acabarian pareciendose pero no siendo iguales. --}}
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card title="Consultas este mes" :value="number_format($mes['total'])"
                     :subtitle="number_format($mes['ruc']) . ' RUC · ' . number_format($mes['dni']) . ' DNI'"
                     color="blue">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Resueltas en casa" :value="number_format($mes['en_casa'])"
                     :subtitle="$mes['total'] ? round($mes['en_casa'] / $mes['total'] * 100) . '% sin salir a internet' : 'Sin consultas todavía'"
                     color="green">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Fueron al proveedor" :value="number_format($mes['al_proveedor'])"
                     subtitle="Lo que cuesta de verdad" color="orange">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Padrón local" :value="$padron ? number_format($padron) : 'Vacío'"
                     :subtitle="$padron ? 'RUC en tu base' : 'Sin importar todavía'"
                     :color="$padron ? 'indigo' : 'yellow'">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </section>

    <nav class="overflow-x-auto border-b border-gray-200">
        <div class="flex gap-1 whitespace-nowrap">
            @foreach($pestanas as $clave => $nombre)
                <a href="{{ route('super-admin.consultas', ['tab' => $clave]) }}"
                   class="border-b-2 px-4 py-3 text-sm transition
                          {{ $actual === $clave
                              ? 'border-blue-600 font-semibold text-blue-600'
                              : 'border-transparent text-gray-600 hover:border-gray-300 hover:text-gray-900' }}">
                    {{ $nombre }}
                </a>
            @endforeach
        </div>
    </nav>

    @includeIf('super-admin.consultas.tabs.' . $actual)

</div>
@endsection
