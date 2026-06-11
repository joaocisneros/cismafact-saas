@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->id)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Ticket #{{ $ticket->id }}</h1>
            <p class="text-gray-500 mt-1">{{ $ticket->subject }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('super-admin.support') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Volver</a>
            @if($ticket->status === 'closed')
                <form action="{{ route('super-admin.support.reopen', $ticket) }}" method="POST" onsubmit="return confirm('¿Reabrir ticket?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">Reabrir Ticket</button>
                </form>
            @elseif($ticket->status !== 'closed')
                <form action="{{ route('super-admin.support.close', $ticket) }}" method="POST" onsubmit="return confirm('¿Cerrar ticket?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Cerrar Ticket</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <span class="text-blue-600 font-medium">{{ substr($ticket->user->name ?? 'U', 0, 1) }}</span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">{{ $ticket->user->name ?? 'Usuario' }}</p>
                        <p class="text-xs text-gray-500">{{ $ticket->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                <p class="text-gray-700 whitespace-pre-wrap">{{ $ticket->message }}</p>
            </div>

            @foreach($ticket->replies as $reply)
            <div class="bg-white rounded-xl shadow-sm p-6 {{ $reply->is_admin ? 'border-l-4 border-blue-500' : '' }}">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 {{ $reply->is_admin ? 'bg-blue-100' : 'bg-gray-100' }} rounded-full flex items-center justify-center">
                        <span class="{{ $reply->is_admin ? 'text-blue-600' : 'text-gray-600' }} font-medium">
                            {{ substr($reply->user->name ?? 'U', 0, 1) }}
                        </span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">{{ $reply->user->name ?? 'Usuario' }} {{ $reply->is_admin ? '(Admin)' : '' }}</p>
                        <p class="text-xs text-gray-500">{{ $reply->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                <p class="text-gray-700 whitespace-pre-wrap">{{ $reply->message }}</p>
            </div>
            @endforeach

            @if($ticket->status !== 'closed')
            <form action="{{ route('super-admin.support.reply', $ticket) }}" method="POST" class="bg-white rounded-xl shadow-sm p-6">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-2">Responder</label>
                <textarea name="message" rows="4" maxlength="5000" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Escribe tu respuesta..."></textarea>
                <div class="mt-3 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Enviar Respuesta</button>
                </div>
            </form>
            @endif
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Detalles</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Estado:</span>
                        @php
                            $statusClasses = [
                                'open' => 'bg-amber-50 text-amber-700',
                                'in_progress' => 'bg-blue-50 text-blue-700',
                                'closed' => 'bg-green-50 text-green-700',
                            ];
                            $statusLabels = ['open' => 'Abierto', 'in_progress' => 'En Progreso', 'closed' => 'Cerrado'];
                        @endphp
                        <span class="rounded px-2 py-1 text-xs {{ $statusClasses[$ticket->status] }}">
                            {{ $statusLabels[$ticket->status] }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Prioridad:</span>
                        @php
                            $priorityClasses = [
                                'low' => 'bg-gray-100 text-gray-700',
                                'medium' => 'bg-amber-50 text-amber-700',
                                'high' => 'bg-red-50 text-red-700',
                            ];
                            $prioridadLabels = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta'];
                        @endphp
                        <span class="rounded px-2 py-1 text-xs {{ $priorityClasses[$ticket->priority] }}">
                            {{ $prioridadLabels[$ticket->priority] }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Empresa:</span>
                        <span class="text-gray-800">{{ $ticket->company->razon_social ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Creado:</span>
                        <span class="text-gray-800">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
