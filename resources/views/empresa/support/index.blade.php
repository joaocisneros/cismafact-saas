@extends('layouts.app')

@section('title', 'Mis Tickets')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Mis Tickets</h1>
            <p class="text-gray-500 mt-1">Historial de tickets de soporte</p>
        </div>
        <a href="{{ route('empresa.support.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
            + Nuevo Ticket
        </a>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['abiertos'] }}</p>
            <p class="text-sm text-gray-500">Abiertos</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['progreso'] }}</p>
            <p class="text-sm text-gray-500">En Progreso</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $stats['cerrados'] }}</p>
            <p class="text-sm text-gray-500">Cerrados</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="text-left py-3 px-4 font-medium text-gray-500">#</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Asunto</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Estado</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Prioridad</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Respuestas</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                    <tr class="border-b hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('empresa.support.show', $ticket) }}'">
                        <td class="py-3 px-4">{{ $ticket->id }}</td>
                        <td class="py-3 px-4 font-medium">{{ $ticket->subject }}</td>
                        <td class="py-3 px-4">
                            @php
                                $statusColors = ['open' => 'yellow', 'in_progress' => 'blue', 'closed' => 'green'];
                                $statusLabels = ['open' => 'Abierto', 'in_progress' => 'En Progreso', 'closed' => 'Cerrado'];
                            @endphp
                            <span class="px-2 py-1 bg-{{ $statusColors[$ticket->status] }}-100 text-{{ $statusColors[$ticket->status] }}-700 rounded text-xs">
                                {{ $statusLabels[$ticket->status] }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            @php
                                $prioridadColors = ['low' => 'gray', 'medium' => 'yellow', 'high' => 'red'];
                                $prioridadLabels = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta'];
                            @endphp
                            <span class="px-2 py-1 bg-{{ $prioridadColors[$ticket->priority] }}-100 text-{{ $prioridadColors[$ticket->priority] }}-700 rounded text-xs">
                                {{ $prioridadLabels[$ticket->priority] }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-500">{{ $ticket->replies_count ?? $ticket->replies->count() }}</td>
                        <td class="py-3 px-4 text-gray-500 text-xs">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-500">No has creado ningún ticket aún.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t">
            {{ $tickets->links() }}
        </div>
    </div>
</div>
@endsection
