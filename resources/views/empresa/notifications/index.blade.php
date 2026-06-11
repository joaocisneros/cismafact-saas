@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Notificaciones</h1>
            <p class="text-gray-500 mt-1">Avisos importantes sobre tu cuenta y tus comprobantes.</p>
        </div>
        @if(auth()->user()->unreadNotifications->count())
            <form method="POST" action="{{ route('empresa.notifications.read-all') }}">
                @csrf
                <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">Marcar todas como leídas</button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
        @forelse($notifications as $notification)
            @php $d = $notification->data; @endphp
            <a href="{{ route('empresa.notifications.read', $notification->id) }}"
               class="flex items-start gap-3 px-5 py-4 hover:bg-gray-50 {{ $notification->read_at ? '' : 'bg-blue-50/40' }}">
                <span class="text-xl">{{ $d['icono'] ?? '🔔' }}</span>
                <div class="flex-1">
                    <p class="font-medium text-gray-900">{{ $d['titulo'] ?? 'Notificación' }}</p>
                    <p class="text-sm text-gray-600">{{ $d['mensaje'] ?? '' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
                @unless($notification->read_at)
                    <span class="mt-1 h-2 w-2 rounded-full bg-blue-500"></span>
                @endunless
            </a>
        @empty
            <div class="px-5 py-10 text-center text-gray-500">No tienes notificaciones.</div>
        @endforelse
    </div>

    <div>{{ $notifications->links() }}</div>
</div>
@endsection
