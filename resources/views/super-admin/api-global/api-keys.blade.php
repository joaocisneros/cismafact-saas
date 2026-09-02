@extends('layouts.app')

@section('title', 'API Keys')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('super-admin.api-global') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">API Keys</h1>
                <p class="text-gray-500 mt-1">Credenciales de todas las empresas</p>
            </div>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-500">{{ $apiKeys->total() }} API keys</span>
        </div>
        <form method="GET" class="flex gap-2 w-full sm:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, key o empresa..."
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            <select name="company_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Todas</option>
                @foreach($companies as $c)
                    <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->razon_social }}</option>
                @endforeach
            </select>
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Todas</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activas</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivas</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Buscar</button>
        </form>
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
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Nombre</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Empresa</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Credencial</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Estado</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Último Uso</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Caduca</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($apiKeys as $apiKey)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $apiKey->name }}</td>
                        <td class="py-3 px-4 text-gray-500 text-xs">{{ $apiKey->company->razon_social ?? 'N/A' }}</td>
                        <td class="py-3 px-4 text-gray-500 text-xs font-mono">{{ substr($apiKey->key, 0, 16) }}...</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 {{ $apiKey->active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} rounded text-xs">
                                {{ $apiKey->active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-500 text-xs">{{ optional($apiKey->last_used_at)->diffForHumans() ?? 'Nunca' }}</td>
                        <td class="py-3 px-4 text-xs">
                            @if (! $apiKey->expires_at)
                                <span class="text-gray-400">Sin caducidad</span>
                            @elseif ($apiKey->expires_at->isPast())
                                <span class="text-red-600 font-medium">Expirada {{ $apiKey->expires_at->format('d/m/Y') }}</span>
                            @else
                                <span class="text-gray-600">{{ $apiKey->expires_at->format('d/m/Y') }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" onclick="window.openAdminModal('{{ route('super-admin.api-global.show-key', $apiKey) }}', 'Detalle de API Key')" class="text-blue-600 hover:text-blue-800 text-sm">Ver</button>
                                {{-- Genera el secret y abre la ficha con el resultado:
                                     ahi es donde se consulta, y no queda a la vista de
                                     quien este mirando la pantalla. --}}
                                <form method="POST" action="{{ route('super-admin.api-global.regenerate-key', $apiKey) }}"
                                      @submit.prevent="if (confirm('Se genera un Secret nuevo para «{{ $apiKey->name }}». El actual deja de funcionar al instante, así que hay que pasarle el nuevo al cliente. La X-Api-Key no cambia. ¿Seguir?')) window.enviarYAbrirModal($el, '{{ route('super-admin.api-global.show-key', $apiKey) }}', 'Detalle de API Key')">
                                    @csrf
                                    <button type="submit" class="text-sm text-amber-600 hover:text-amber-800">Nuevo Secret</button>
                                </form>
                                <form method="POST" action="{{ route('super-admin.api-global.toggle-key', $apiKey) }}" onsubmit="return confirm('Cambiar estado de la API Key?')">
                                    @csrf
                                    <button type="submit" class="text-{{ $apiKey->active ? 'red' : 'green' }}-600 hover:text-{{ $apiKey->active ? 'red' : 'green' }}-800 text-sm">
                                        {{ $apiKey->active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('super-admin.api-global.extend-key', $apiKey) }}" class="flex items-center gap-1">
                                    @csrf
                                    <input type="number" name="days" value="30" min="1" max="365"
                                           class="w-14 rounded border border-gray-300 px-1 py-0.5 text-xs">
                                    <button type="submit" class="text-indigo-600 hover:text-indigo-800 text-sm">Extender</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500">No se encontraron credenciales con esos filtros.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t">
            {{ $apiKeys->links() }}
        </div>
    </div>
</div>
@endsection
