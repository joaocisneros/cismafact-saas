@extends('layouts.app')

@section('title', 'Certificados')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Gestor de Certificados</h1>
        <p class="text-gray-500 mt-1">Estado de los certificados digitales de todas las empresas.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-stat-card title="Vigentes" :value="$stats['vigentes']" color="green">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Por vencer (≤30 días)" :value="$stats['por_vencer']" color="yellow">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Vencidos" :value="$stats['vencidos']" color="red">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Sin certificado" :value="$stats['sin_certificado']" color="blue">
            <x-slot:icon>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-5 py-3">Empresa</th>
                    <th class="px-5 py-3">Titular del certificado</th>
                    <th class="px-5 py-3">RUC cert.</th>
                    <th class="px-5 py-3">Vence</th>
                    <th class="px-5 py-3">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($companies as $company)
                    @php
                        $badge = [
                            'green'  => 'bg-green-100 text-green-700',
                            'yellow' => 'bg-yellow-100 text-yellow-700',
                            'red'    => 'bg-red-100 text-red-700',
                            'gray'   => 'bg-gray-100 text-gray-600',
                        ][$company->certEstadoColor()];
                    @endphp
                    <tr>
                        <td class="px-5 py-4">
                            <p class="font-medium text-gray-900">{{ $company->razon_social }}</p>
                            <p class="text-xs text-gray-500">{{ $company->ruc }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600">{{ $company->cert_titular ?? '—' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $company->cert_ruc ?? '—' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ optional($company->cert_valido_hasta)->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $badge }}">
                                {{ $company->certEstadoLabel() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-5 py-8 text-center text-gray-500" colspan="5">No hay empresas registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $companies->links() }}</div>
</div>
@endsection
