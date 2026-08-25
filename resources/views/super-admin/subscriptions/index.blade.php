@extends('layouts.app')

@section('title', 'Suscripciones')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Suscripciones</h2>
            <p class="mt-1 text-sm text-gray-500">Planes, vigencias y renovación de empresas.</p>
        </div>
        <button type="button"
                onclick="window.openAdminModal('{{ route('super-admin.subscriptions.create') }}', 'Nueva suscripción')"
                class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            Nueva o actualizar
        </button>
    </div>

    <form method="GET" class="grid gap-3 border-y border-gray-200 bg-white py-4 md:grid-cols-4">
        <input name="search" value="{{ request('search') }}" placeholder="RUC o razón social"
               class="rounded-md border border-gray-300 px-3 py-2 text-sm">
        <select name="plan_id" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <option value="">Todos los planes</option>
            @foreach($plans as $plan)
                <option value="{{ $plan->id }}" @selected((string) request('plan_id') === (string) $plan->id)>{{ $plan->name }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <option value="">Todos los estados</option>
            @foreach(['trial' => 'Prueba', 'active' => 'Activa', 'suspended' => 'Suspendida', 'cancelled' => 'Cancelada', 'expired' => 'Vencida'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white">Filtrar</button>
            <a href="{{ route('super-admin.subscriptions.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700">Limpiar</a>
        </div>
    </form>

    <div class="overflow-x-auto border-y border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Empresa</th>
                    <th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Vigencia</th>
                    <th class="px-4 py-3">Renovación</th>
                    <th class="px-4 py-3">Precio</th>
                    <th class="px-4 py-3">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($subscriptions as $subscription)
                    @php
                        $statusLabels = ['trial' => 'Prueba', 'active' => 'Activa', 'suspended' => 'Suspendida', 'cancelled' => 'Cancelada', 'expired' => 'Vencida'];
                        $statusClasses = ['trial' => 'bg-blue-50 text-blue-700', 'active' => 'bg-green-50 text-green-700', 'suspended' => 'bg-amber-50 text-amber-700', 'cancelled' => 'bg-gray-100 text-gray-700', 'expired' => 'bg-red-50 text-red-700'];
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $subscription->company?->razon_social ?? 'Empresa eliminada' }}</div>
                            <div class="text-xs text-gray-500">{{ $subscription->company?->ruc ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $subscription->plan?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded px-2 py-1 text-xs font-medium {{ $statusClasses[$subscription->status] ?? 'bg-gray-100' }}">
                                {{ $statusLabels[$subscription->status] ?? $subscription->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $subscription->starts_at->format('d/m/Y') }}
                            <span class="block text-xs text-gray-400">hasta {{ $subscription->ends_at?->format('d/m/Y') ?? 'sin vencimiento' }}</span>
                            @if($subscription->ends_at && $subscription->status !== 'expired')
                                <span class="block text-xs text-gray-400">{{ $subscription->ends_at->diffForHumans() }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <span class="{{ $subscription->auto_renew ? 'text-green-700' : 'text-gray-500' }}">
                                {{ $subscription->auto_renew ? 'Automática' : 'Manual' }}
                            </span>
                            @if($subscription->next_billing_at)
                                <span class="block text-xs text-gray-400">{{ $subscription->next_billing_at->format('d/m/Y') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">S/ {{ number_format((float) $subscription->monthly_price, 2) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <x-icon-action icon="editar" label="Editar suscripción" color="slate"
                                               :href="route('super-admin.subscriptions.edit', $subscription)"
                                               @click.prevent="loadAdminModal('{{ route('super-admin.subscriptions.edit', $subscription) }}', 'Editar suscripción')" />
                                <form method="POST" action="{{ route('super-admin.subscriptions.renew', $subscription) }}" class="flex items-center gap-1">
                                    @csrf
                                    <select name="months" class="rounded border border-gray-300 px-1 py-1 text-xs">
                                        @foreach([1, 3, 6, 12] as $months)
                                            <option value="{{ $months }}">{{ $months }} mes{{ $months > 1 ? 'es' : '' }}</option>
                                        @endforeach
                                    </select>
                                    <x-icon-action icon="renovar" label="Renovar suscripción" color="blue" />
                                </form>
                                @if(in_array($subscription->status, ['active', 'trial'], true))
                                    <form method="POST" action="{{ route('super-admin.subscriptions.status', $subscription) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="suspended">
                                        <x-icon-action icon="suspender" label="Suspender suscripción" color="amber" />
                                    </form>
                                @elseif($subscription->status === 'suspended' && (! $subscription->ends_at || $subscription->ends_at->isToday() || $subscription->ends_at->isFuture()))
                                    <form method="POST" action="{{ route('super-admin.subscriptions.status', $subscription) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="active">
                                        <x-icon-action icon="activar" label="Activar suscripción" color="emerald" />
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">No hay suscripciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $subscriptions->links() }}</div>
</div>
@endsection
