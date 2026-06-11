@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->id)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('empresa.support.index') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Ticket #{{ $ticket->id }}</h1>
                <p class="text-gray-500 mt-1">{{ $ticket->subject }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            @php
                $statusColors = ['open' => 'yellow', 'in_progress' => 'blue', 'closed' => 'green'];
                $statusLabels = ['open' => 'Abierto', 'in_progress' => 'En Progreso', 'closed' => 'Cerrado'];
            @endphp
            <span class="px-3 py-1 bg-{{ $statusColors[$ticket->status] }}-100 text-{{ $statusColors[$ticket->status] }}-700 rounded text-sm font-medium">
                {{ $statusLabels[$ticket->status] }}
            </span>
            @if($ticket->status === 'closed')
                <form action="{{ route('empresa.support.reopen', $ticket) }}" method="POST" onsubmit="return confirm('¿Reabrir ticket?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 text-sm">Reabrir</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <span class="text-blue-600 font-medium">{{ substr($ticket->user->name ?? 'Tú', 0, 1) }}</span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">{{ $ticket->user->name ?? 'Tú' }}</p>
                        <p class="text-xs text-gray-500">{{ $ticket->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                <p class="text-gray-700 whitespace-pre-wrap">{{ $ticket->message }}</p>
            </div>

            @foreach($ticket->replies as $reply)
            <div class="bg-white rounded-xl shadow-sm p-6 {{ $reply->is_admin ? 'border-l-4 border-green-500' : '' }}">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 {{ $reply->is_admin ? 'bg-green-100' : 'bg-gray-100' }} rounded-full flex items-center justify-center">
                        <span class="{{ $reply->is_admin ? 'text-green-600' : 'text-gray-600' }} font-medium">
                            {{ substr($reply->user->name ?? 'U', 0, 1) }}
                        </span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">
                            {{ $reply->user->name ?? 'Usuario' }}
                            @if($reply->is_admin)
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded ml-1">Soporte</span>
                            @endif
                        </p>
                        <p class="text-xs text-gray-500">{{ $reply->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                <p class="text-gray-700 whitespace-pre-wrap">{{ $reply->message }}</p>
            </div>
            @endforeach

            @if($ticket->status !== 'closed')
            <form action="{{ route('empresa.support.reply', $ticket) }}" method="POST" class="bg-white rounded-xl shadow-sm p-6">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-2">Responder</label>
                <textarea name="message" rows="4" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Escribe tu respuesta..."></textarea>
                <div class="mt-3 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Enviar Respuesta</button>
                </div>
            </form>
            @else
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-center">
                <p class="text-sm text-gray-500">Este ticket está cerrado.</p>
            </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Detalles</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Estado:</span>
                        <span class="px-2 py-1 bg-{{ $statusColors[$ticket->status] }}-100 text-{{ $statusColors[$ticket->status] }}-700 rounded text-xs">
                            {{ $statusLabels[$ticket->status] }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Prioridad:</span>
                        @php
                            $prioridadColors = ['low' => 'gray', 'medium' => 'yellow', 'high' => 'red'];
                            $prioridadLabels = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta'];
                        @endphp
                        <span class="px-2 py-1 bg-{{ $prioridadColors[$ticket->priority] }}-100 text-{{ $prioridadColors[$ticket->priority] }}-700 rounded text-xs">
                            {{ $prioridadLabels[$ticket->priority] }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Creado:</span>
                        <span class="text-gray-800">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Respuestas:</span>
                        <span class="text-gray-800">{{ $ticket->replies->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
