@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

@section('content')
<div class="space-y-6">
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-stat-card title="Empresas Activas" :value="number_format($stats['activeCompanies'])" color="blue">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Usuarios Registrados" :value="number_format($stats['registeredUsers'])" color="indigo">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2a4 4 0 10-8 0 4 4 0 008 0zm6-3a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Documentos Emitidos" :value="number_format($stats['issuedDocuments'])" color="green">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        @php $sistemaOperativo = $stats['systemStatus'] === 'Operativo'; @endphp
        <x-stat-card title="Estado del Sistema" :value="$stats['systemStatus']" :color="$sistemaOperativo ? 'green' : 'red'">
            <x-slot:icon>
                @if($sistemaOperativo)
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                @endif
            </x-slot:icon>
        </x-stat-card>
    </section>

    {{-- Eran tres rectangulos de color con el numero escondido dentro de una
         frase y un "Revisar" subrayado. Lo que importa de una alerta es cuantos
         son, asi que el numero manda y la tarjeta entera lleva al listado. --}}
    <section>
        <h2 class="mb-3 text-base font-semibold text-gray-900">Alertas</h2>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($alerts as $alert)
                @php
                    $tono = [
                        'danger'  => ['borde' => 'border-red-200 hover:border-red-300',    'punto' => 'bg-red-500',   'cifra' => 'text-red-700'],
                        'warning' => ['borde' => 'border-amber-200 hover:border-amber-300','punto' => 'bg-amber-500', 'cifra' => 'text-amber-700'],
                        'info'    => ['borde' => 'border-blue-200 hover:border-blue-300',  'punto' => 'bg-blue-500',  'cifra' => 'text-blue-700'],
                        'success' => ['borde' => 'border-green-200',                       'punto' => 'bg-green-500', 'cifra' => 'text-green-700'],
                    ][$alert['type']] ?? ['borde' => 'border-gray-200', 'punto' => 'bg-gray-400', 'cifra' => 'text-gray-700'];

                    $etiqueta = $alert['route'] ? 'a' : 'div';
                @endphp

                <{{ $etiqueta }} @if($alert['route']) href="{{ $alert['route'] }}" @endif
                   class="group flex flex-col justify-between rounded-lg border bg-white p-4 shadow-sm transition {{ $tono['borde'] }} @if($alert['route']) hover:shadow-md @endif">

                    <div class="flex items-start gap-2.5">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $tono['punto'] }}"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $alert['title'] }}</p>

                            @if(($alert['count'] ?? null) !== null)
                                <p class="mt-1.5 text-2xl font-semibold leading-none {{ $tono['cifra'] }}">{{ $alert['count'] }}</p>
                            @endif

                            <p class="mt-1.5 text-xs text-gray-500">{{ $alert['message'] }}</p>
                        </div>
                    </div>

                    @if($alert['route'])
                        <span class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-gray-400 transition group-hover:text-gray-700">
                            Ver
                            <svg class="h-3 w-3 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    @endif
                </{{ $etiqueta }}>
            @endforeach
        </div>
    </section>

    <section class="grid grid-cols-1 items-start gap-6 xl:grid-cols-2">
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">Últimas 5 Empresas Registradas</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Empresa</th>
                            <th class="px-5 py-3">RUC</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3">Registro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($latestCompanies as $company)
                            <tr>
                                <td class="px-5 py-4 font-medium text-gray-900">{{ $company->razon_social }}</td>
                                <td class="px-5 py-4 text-gray-600">{{ $company->ruc }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $company->activo ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $company->activo ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ optional($company->created_at)->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-5 py-8 text-center text-gray-500" colspan="4">No hay empresas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">Últimos 5 Documentos Emitidos</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Tipo</th>
                            <th class="px-5 py-3">Documento</th>
                            <th class="px-5 py-3">Empresa</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($latestDocuments as $document)
                            <tr>
                                <td class="px-5 py-4 text-gray-700">{{ $document->tipo }}</td>
                                <td class="px-5 py-4 font-medium text-gray-900">{{ $document->numero_completo }}</td>
                                <td class="px-5 py-4 text-gray-600">{{ $document->empresa ?? 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                        {{ $document->estado_sunat }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ \Carbon\Carbon::parse($document->created_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-5 py-8 text-center text-gray-500" colspan="5">No hay documentos emitidos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </section>
</div>
@endsection
