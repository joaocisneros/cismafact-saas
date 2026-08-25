@extends('layouts.app')

@section('title', 'Planes')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Planes</h2>
                <p class="mt-1 text-sm text-gray-500">Administra límites, precios y asignaciones comerciales.</p>
            </div>
            <button type="button" onclick="window.openAdminModal('{{ route('super-admin.plans.create') }}', 'Crear plan')" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                Crear plan
            </button>
        </div>

        <form method="GET" class="grid gap-3 border-y border-gray-200 bg-white py-4 sm:grid-cols-[1fr_220px_auto]">
            <input name="search" value="{{ request('search') }}" placeholder="Buscar plan..."
                   class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <select name="status" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">Todos los estados</option>
                <option value="active" @selected(request('status') === 'active')>Activos</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactivos</option>
            </select>
            <div class="flex gap-2">
                <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white">Filtrar</button>
                <a href="{{ route('super-admin.plans') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700">Limpiar</a>
            </div>
        </form>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Plan</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Precio</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Docs/mes</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Usuarios</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">API Requests</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Soporte</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Empresas</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Estado</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($plans as $plan)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="text-sm font-semibold text-gray-900">{{ $plan->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $plan->code }}</div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">S/ {{ number_format((float) $plan->monthly_price, 2) }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">{{ $plan->monthly_document_limit ? number_format($plan->monthly_document_limit) : 'Sin límite' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">{{ $plan->user_limit ? number_format($plan->user_limit) : 'Sin límite' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">{{ $plan->api_request_limit ? number_format($plan->api_request_limit) : 'Sin límite' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">{{ $plan->support_included }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">{{ $plan->companies_count }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $plan->active ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-gray-50 text-gray-600 ring-gray-500/20' }}">
                                        {{ $plan->active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <x-icon-action icon="editar" label="Editar plan" color="blue" type="button"
                                                       onclick="window.openAdminModal('{{ route('super-admin.plans.edit', $plan) }}', 'Editar plan')" />
                                        <form method="POST" action="{{ route('super-admin.plans.toggle', $plan) }}">
                                            @csrf
                                            <x-icon-action :icon="$plan->active ? 'suspender' : 'activar'"
                                                           :label="$plan->active ? 'Desactivar plan' : 'Activar plan'"
                                                           :color="$plan->active ? 'amber' : 'emerald'" />
                                        </form>
                                        <form method="POST" action="{{ route('super-admin.plans.destroy', $plan) }}" onsubmit="return confirm('Eliminar plan {{ $plan->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-icon-action icon="eliminar" label="Eliminar plan" color="red" />
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-8 text-center text-sm text-gray-500">No hay planes registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 px-5 py-3">
                {{ $plans->links() }}
            </div>
        </div>
    </div>
@endsection
