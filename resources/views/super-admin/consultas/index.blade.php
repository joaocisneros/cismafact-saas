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

    {{-- Cosas que sirven para decidir algo. Antes habia aqui "resueltas en
         casa" y "fueron al proveedor": eso es como esta hecho por dentro, no
         algo sobre lo que se actue. Y el padron no es una cifra, es un aviso.
         Van con el componente del Dashboard, no unas parecidas a mano. --}}
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card title="Consultas este mes" :value="number_format($cabecera['mes'])"
                     :subtitle="number_format($mes['ruc']) . ' RUC · ' . number_format($mes['dni']) . ' DNI'"
                     color="blue">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Hoy" :value="number_format($cabecera['hoy'])"
                     subtitle="Consultas en lo que va de día" color="indigo">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Empresas usándola" :value="number_format($cabecera['empresas'])"
                     subtitle="Consultaron al menos una vez este mes" color="green">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2a4 4 0 10-8 0 4 4 0 008 0zm6-3a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Cerca de su límite" :value="number_format($cabecera['cerca_del_tope'])"
                     subtitle="Han pasado del 80% de su cuota"
                     :color="$cabecera['cerca_del_tope'] ? 'orange' : 'green'">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
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
