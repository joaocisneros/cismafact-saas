@extends('layouts.app')

@section('title', 'API Key - ' . $apiKey->name)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('super-admin.api-global') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">{{ $apiKey->name }}</h1>
            <span class="px-2 py-1 {{ $apiKey->active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} rounded text-xs font-medium">
                {{ $apiKey->active ? 'Activa' : 'Inactiva' }}
            </span>
        </div>
        <form method="POST" action="{{ route('super-admin.api-global.toggle-key', $apiKey) }}" onsubmit="return confirm('¿{{ $apiKey->active ? 'Desactivar' : 'Activar' }} API Key?')">
            @csrf
            <button type="submit" class="px-4 py-2 {{ $apiKey->active ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg text-sm">
                {{ $apiKey->active ? 'Desactivar' : 'Activar' }}
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <x-stat-card title="Total Llamadas" :value="number_format($totalUsage)" color="blue" />
        <x-stat-card title="Último Uso" :value="optional($apiKey->last_used_at)->diffForHumans() ?? 'Nunca'" color="green" />
        <x-stat-card title="Expira" :value="$apiKey->expires_at ? $apiKey->expires_at->format('d/m/Y') : 'Nunca'" color="purple" />
        <x-stat-card title="Empresa" :value="$apiKey->company->razon_social ?? 'N/A'" color="orange" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-4">Detalles de la API Key</h3>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Nombre:</dt>
                    <dd class="text-sm font-medium">{{ $apiKey->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Key:</dt>
                    <dd class="text-sm font-medium font-mono">{{ $apiKey->key }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Empresa:</dt>
                    <dd class="text-sm font-medium">{{ $apiKey->company->razon_social ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Creada:</dt>
                    <dd class="text-sm font-medium">{{ $apiKey->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-4">Últimos Usos</h3>
            @if($recentUsage->count() > 0)
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach($recentUsage as $usage)
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded text-sm">
                        <div class="flex items-center gap-2">
                            <span class="px-1 py-0.5 {{ $usage->status_code >= 400 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }} rounded text-xs">{{ $usage->status_code }}</span>
                            <span class="text-gray-600 text-xs">{{ $usage->method }} {{ $usage->path }}</span>
                        </div>
                        <span class="text-gray-500 text-xs">{{ optional($usage->created_at)->diffForHumans() }}</span>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm text-center py-4">No hay uso registrado.</p>
            @endif
        </div>
    </div>
</div>
@endsection
