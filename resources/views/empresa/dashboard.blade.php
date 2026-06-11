@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-stat-card title="Ventas Hoy" :value="'S/ ' . number_format($ventasHoy, 2)" color="blue">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Ventas Este Mes" :value="'S/ ' . number_format($ventasMes, 2)" color="green">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Facturas Hoy" :value="$facturasHoy" subtitle="{{ $boletasHoy }} boletas hoy" color="purple">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Consumo API" :value="$consumoApiHoy" subtitle="{{ $consumoApiMes }} este mes" color="orange">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Estado SUNAT</p>
                    <p class="text-lg font-semibold {{ $sunatConfigurado ? 'text-green-600' : 'text-red-600' }}">
                        {{ $sunatConfigurado ? 'Configurado' : 'No configurado' }}
                    </p>
                </div>
                <div class="w-10 h-10 {{ $sunatConfigurado ? 'bg-green-100' : 'bg-red-100' }} rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $sunatConfigurado ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Certificado Digital</p>
                    @if(!$certificadoExiste)
                        <p class="text-lg font-semibold text-orange-600">Pendiente</p>
                    @elseif($certificadoVencido)
                        <p class="text-lg font-semibold text-red-600">Vencido</p>
                    @elseif($certificadoExpira && $certificadoExpira->diffInDays(now()) < 30)
                        <p class="text-lg font-semibold text-yellow-600">Por vencer ({{ $certificadoExpira->diffInDays(now()) }} días)</p>
                    @else
                        <p class="text-lg font-semibold text-green-600">Vigente</p>
                    @endif
                </div>
                <div class="w-10 h-10 {{ $certificadoExiste && !$certificadoVencido ? 'bg-green-100' : 'bg-red-100' }} rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $certificadoExiste && !$certificadoVencido ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
            </div>
            @if($certificadoExpira)
                <p class="text-xs text-gray-500 mt-2">Vence: {{ $certificadoExpira->format('d/m/Y') }}</p>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">API Keys Activas</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $apiKeysActivas }}</p>
                </div>
                <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    @if(!$sunatConfigurado)
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 flex items-center gap-3">
        <svg class="w-5 h-5 text-yellow-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
        </svg>
        <div>
            <p class="text-sm font-medium text-yellow-800">Configuración SUNAT pendiente</p>
            <p class="text-xs text-yellow-600">Para comenzar a facturar, configura tus credenciales SOL y certificado digital.</p>
        </div>
        <a href="{{ route('empresa.sunat-config.index') }}" class="ml-auto px-4 py-2 bg-yellow-600 text-white text-sm rounded-lg hover:bg-yellow-700">
            Configurar
        </a>
    </div>
    @endif

    @if($certificadoVencido)
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-center gap-3">
        <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="text-sm font-medium text-red-800">Certificado digital vencido</p>
            <p class="text-xs text-red-600">Tu certificado SUNAT ha vencido. Actualízalo para continuar facturando.</p>
        </div>
        <a href="{{ route('empresa.sunat-config.index') }}" class="ml-auto px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">
            Actualizar
        </a>
    </div>
    @endif

    @if($apiKeysActivas === 0)
    <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 flex items-center gap-3">
        <svg class="w-5 h-5 text-orange-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
        </svg>
        <div>
            <p class="text-sm font-medium text-orange-800">No hay API Keys activas</p>
            <p class="text-xs text-orange-600">Crea una API Key para que tu sistema pueda conectarse a la facturación.</p>
        </div>
        <a href="{{ route('empresa.api-keys.index') }}" class="ml-auto px-4 py-2 bg-orange-600 text-white text-sm rounded-lg hover:bg-orange-700">
            Crear API Key
        </a>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Últimos Documentos</h2>
        @if($ultimasFacturas->count() > 0 || $ultimasBoletas->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="pb-3 font-medium">Tipo</th>
                            <th class="pb-3 font-medium">Serie</th>
                            <th class="pb-3 font-medium">Número</th>
                            <th class="pb-3 font-medium">Fecha</th>
                            <th class="pb-3 font-medium">Total</th>
                            <th class="pb-3 font-medium">Estado SUNAT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ultimasFacturas->take(3) as $doc)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3"><span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Factura</span></td>
                            <td class="py-3">{{ $doc->serie }}</td>
                            <td class="py-3">{{ $doc->numero_completo }}</td>
                            <td class="py-3 text-gray-500">{{ $doc->fecha_emision?->format('d/m/Y') ?? '-' }}</td>
                            <td class="py-3">S/ {{ number_format($doc->mto_imp_venta, 2) }}</td>
                            <td class="py-3"><x-status-badge :status="$doc->estado_sunat" /></td>
                        </tr>
                        @endforeach
                        @foreach($ultimasBoletas->take(3) as $doc)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3"><span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Boleta</span></td>
                            <td class="py-3">{{ $doc->serie }}</td>
                            <td class="py-3">{{ $doc->numero_completo }}</td>
                            <td class="py-3 text-gray-500">{{ $doc->fecha_emision?->format('d/m/Y') ?? '-' }}</td>
                            <td class="py-3">S/ {{ number_format($doc->mto_imp_venta, 2) }}</td>
                            <td class="py-3"><x-status-badge :status="$doc->estado_sunat" /></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 text-sm text-center py-4">No hay documentos emitidos aún.</p>
        @endif
    </div>
</div>
@endsection
