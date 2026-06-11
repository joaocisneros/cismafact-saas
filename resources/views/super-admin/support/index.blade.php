@extends('layouts.app')

@section('title', 'Soporte')

@section('content')
<div class="space-y-5">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Tickets de soporte</h2>
        <p class="mt-1 text-sm text-gray-500">Seguimiento de consultas e incidencias de las empresas.</p>
    </div>

    <dl class="grid grid-cols-3 divide-x divide-gray-200 border-y border-gray-200 bg-white">
        <div class="p-4">
            <dt class="text-xs font-medium uppercase text-gray-500">Abiertos</dt>
            <dd class="mt-1 text-2xl font-semibold text-amber-700">{{ $stats['abiertos'] }}</dd>
        </div>
        <div class="p-4">
            <dt class="text-xs font-medium uppercase text-gray-500">En progreso</dt>
            <dd class="mt-1 text-2xl font-semibold text-blue-700">{{ $stats['progreso'] }}</dd>
        </div>
        <div class="p-4">
            <dt class="text-xs font-medium uppercase text-gray-500">Cerrados</dt>
            <dd class="mt-1 text-2xl font-semibold text-green-700">{{ $stats['cerrados'] }}</dd>
        </div>
    </dl>

    <form method="GET" class="grid gap-3 border-y border-gray-200 bg-white py-4 md:grid-cols-4">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Asunto o mensaje"
               class="rounded-md border border-gray-300 px-3 py-2 text-sm">
        <select name="status" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <option value="">Todos los estados</option>
            <option value="open" @selected(request('status') === 'open')>Abierto</option>
            <option value="in_progress" @selected(request('status') === 'in_progress')>En progreso</option>
            <option value="closed" @selected(request('status') === 'closed')>Cerrado</option>
        </select>
        <select name="priority" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <option value="">Todas las prioridades</option>
            <option value="low" @selected(request('priority') === 'low')>Baja</option>
            <option value="medium" @selected(request('priority') === 'medium')>Media</option>
            <option value="high" @selected(request('priority') === 'high')>Alta</option>
        </select>
        <div class="flex gap-2">
            <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white">Filtrar</button>
            <a href="{{ route('super-admin.support') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700">Limpiar</a>
        </div>
    </form>

    <div class="overflow-x-auto border-y border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Ticket</th>
                    <th class="px-4 py-3">Empresa</th>
                    <th class="px-4 py-3">Prioridad</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Respuestas</th>
                    <th class="px-4 py-3">Fecha</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tickets as $ticket)
                    @php
                        $priorityLabels = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta'];
                        $priorityClasses = ['low' => 'bg-gray-100 text-gray-700', 'medium' => 'bg-amber-50 text-amber-700', 'high' => 'bg-red-50 text-red-700'];
                        $statusLabels = ['open' => 'Abierto', 'in_progress' => 'En progreso', 'closed' => 'Cerrado'];
                        $statusClasses = ['open' => 'bg-amber-50 text-amber-700', 'in_progress' => 'bg-blue-50 text-blue-700', 'closed' => 'bg-green-50 text-green-700'];
                    @endphp
                    <tr class="cursor-pointer hover:bg-gray-50"
                        onclick="window.openAdminModal('{{ route('super-admin.support.show', $ticket) }}', 'Ticket #{{ $ticket->id }}')">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">#{{ $ticket->id }} · {{ $ticket->subject }}</div>
                            <div class="text-xs text-gray-500">{{ $ticket->user->name ?? 'N/A' }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $ticket->company->razon_social ?? 'N/A' }}</td>
                        <td class="px-4 py-3"><span class="rounded px-2 py-1 text-xs {{ $priorityClasses[$ticket->priority] }}">{{ $priorityLabels[$ticket->priority] }}</span></td>
                        <td class="px-4 py-3"><span class="rounded px-2 py-1 text-xs {{ $statusClasses[$ticket->status] }}">{{ $statusLabels[$ticket->status] }}</span></td>
                        <td class="px-4 py-3 text-gray-600">{{ $ticket->replies_count }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">No hay tickets con estos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $tickets->links() }}</div>
</div>
@endsection
