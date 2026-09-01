@extends('layouts.app')

@section('title', 'API RUC y DNI')

@section('content')
@php
    // Las pestañas van por la direccion y no por JavaScript: asi se puede
    // enlazar una en concreto, y al guardar un formulario back() vuelve a la
    // misma en vez de tirar al usuario a la primera.
    // Planes va la ultima y no la primera: los precios y las cuotas se tocan
    // el dia que se monta el servicio o cuando cambia una tarifa, mientras que
    // las llaves y el consumo se miran a diario. Abrir el modulo por la
    // configuracion obligaba a pasar de largo por ella cada vez.
    $pestanas = [
        'apis' => 'Mis APIs',
        'sandbox' => 'Sandbox',
        // Dos consumos y no uno: el de fuera es lo que se cobra y descuenta
        // cuota; el de casa cuesta pero no se factura. Sumarlos daria un total
        // que no significa nada, asi que van en pestañas distintas.
        'consumo' => 'Consumo externo',
        'interno' => 'Consumo interno',
        'docs' => 'Documentación',
        'planes' => 'Planes',
    ];

    $actual = array_key_exists(request('tab'), $pestanas) ? request('tab') : 'apis';
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
                     :subtitle="number_format($cabecera['mes_externo']) . ' de clientes · ' . number_format($cabecera['mes_interno']) . ' desde el panel'"
                     color="blue">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        {{-- Las llaves que hay, que es lo que abren las dos primeras pestañas.

             Antes iba «Hoy», que repetia el numero del mes cuando todo lo del
             mes era de hoy y no lleva a hacer nada, y «Empresas usandola», que
             salia 0 al lado de 25 consultas porque contaba empresas del
             sistema y estas llaves se venden a gente de fuera. --}}
        <x-stat-card title="API Keys activas" :value="number_format($cabecera['llaves_produccion'])"
                     :subtitle="number_format($cabecera['llaves_sandbox']) . ' llaves de sandbox además'"
                     color="indigo">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        {{-- Aqui iba «Ingresos al mes».

             Esta pantalla se enseña delante de clientes, y lo que se factura
             no tiene por que salir en ella. En su sitio va lo que si hace
             falta mirar: a quien se le acaba el plazo. --}}
        <x-stat-card title="Caducan en 30 días" :value="number_format($cabecera['caducan_pronto'])"
                     :subtitle="$cabecera['caducan_produccion']
                        ? number_format($cabecera['caducan_produccion']) . ' de ellas son de producción'
                        : 'Ninguna de producción'"
                     :color="$cabecera['caducan_produccion'] ? 'orange' : 'green'">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
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
