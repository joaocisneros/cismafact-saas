@extends('layouts.app')

@section('title', 'Pagos')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Pagos</h2>
            <p class="mt-1 text-sm text-gray-500">Registro manual de cobros y activación de suscripciones.</p>
        </div>
        <button type="button"
                onclick="window.openAdminModal('{{ route('super-admin.payments.create') }}', 'Registrar pago')"
                class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            Registrar pago
        </button>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card title="Total confirmado" value="S/ {{ number_format((float) $stats['total_confirmado'], 2) }}" subtitle="Histórico" color="green">
            <x-slot:icon><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Cobrado este mes" value="S/ {{ number_format((float) $stats['mes_actual'], 2) }}" subtitle="{{ now()->translatedFormat('F Y') }}" color="blue">
            <x-slot:icon><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Pendientes" value="{{ $stats['pendientes'] }}" subtitle="Por confirmar" color="yellow">
            <x-slot:icon><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Registros" value="{{ $stats['total_registros'] }}" subtitle="Pagos totales" color="purple">
            <x-slot:icon><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></x-slot:icon>
        </x-stat-card>
    </div>

    <form method="GET" class="grid gap-3 border-y border-gray-200 bg-white py-4 md:grid-cols-4">
        <input name="search" value="{{ request('search') }}" placeholder="RUC o razón social"
               class="rounded-md border border-gray-300 px-3 py-2 text-sm">
        <select name="method" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <option value="">Todos los métodos</option>
            @foreach(\App\Models\Payment::METODOS as $value => $label)
                <option value="{{ $value }}" @selected(request('method') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <option value="">Todos los estados</option>
            @foreach(\App\Models\Payment::ESTADOS as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white">Filtrar</button>
            <a href="{{ route('super-admin.payments.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700">Limpiar</a>
        </div>
    </form>

    <div class="overflow-x-auto border-y border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Empresa</th>
                    <th class="px-4 py-3">Monto</th>
                    <th class="px-4 py-3">Método</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Meses</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payments as $payment)
                    @php
                        $estadoClasses = ['pending' => 'bg-amber-50 text-amber-700', 'confirmed' => 'bg-green-50 text-green-700', 'refunded' => 'bg-gray-100 text-gray-600'];
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $payment->company?->razon_social ?? 'Empresa eliminada' }}</div>
                            <div class="text-xs text-gray-500">{{ $payment->company?->ruc ?? '—' }}</div>
                            @if($payment->reference)
                                <div class="text-xs text-gray-400">Ref: {{ $payment->reference }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900">S/ {{ number_format((float) $payment->amount, 2) }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $payment->metodoLabel() }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $payment->paid_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $payment->months }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded px-2 py-1 text-xs font-medium {{ $estadoClasses[$payment->status] ?? 'bg-gray-100' }}">
                                {{ $payment->estadoLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                @if($payment->status === 'pending')
                                    <form method="POST" action="{{ route('super-admin.payments.confirm', $payment) }}">
                                        @csrf
                                        <button class="text-sm font-medium text-green-600 hover:text-green-800">Confirmar</button>
                                    </form>
                                @endif
                                @if($payment->status === 'confirmed')
                                    <form method="POST" action="{{ route('super-admin.payments.refund', $payment) }}"
                                          onsubmit="return confirm('¿Marcar este pago como reembolsado?')">
                                        @csrf
                                        <button class="text-sm font-medium text-red-600 hover:text-red-800">Reembolsar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">No hay pagos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $payments->links() }}</div>
</div>
@endsection
