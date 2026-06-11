@extends('layouts.app')

@section('title', 'API Keys')

@section('content')
<div class="space-y-6" x-data="{ copying: null, copy(value, id) { navigator.clipboard.writeText(value); this.copying = id; setTimeout(() => this.copying = null, 1500); } }">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">API Keys</h2>
        <div class="flex gap-2">
            <a href="{{ route('empresa.api-keys.documentation') }}"
               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                📖 Documentación API
            </a>
            <button @click="$dispatch('open-modal', 'create-api-key')"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                + Nueva API Key
            </button>
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
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Nombre</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Credenciales</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Estado</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Último Uso</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($apiKeys as $apiKey)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium">{{ $apiKey->name }}</td>
                        <td class="py-3 px-4">
                            <div class="space-y-2">
                                <div>
                                    <p class="mb-1 text-[11px] font-semibold uppercase text-gray-500">X-Api-Key</p>
                                    <div class="flex max-w-xl items-center gap-2 rounded-md bg-gray-50 px-3 py-2">
                                        <code class="min-w-0 flex-1 truncate font-mono text-xs text-gray-800">{{ $apiKey->key }}</code>
                                        <button type="button" @click="copy(@js($apiKey->key), 'key-{{ $apiKey->id }}')" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                            <span x-text="copying === 'key-{{ $apiKey->id }}' ? 'Copiado' : 'Copiar'"></span>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <p class="mb-1 text-[11px] font-semibold uppercase text-gray-500">X-Api-Secret</p>
                                    <div class="flex max-w-xl items-center gap-2 rounded-md bg-gray-50 px-3 py-2">
                                        @if($apiKey->plain_secret)
                                            <code class="min-w-0 flex-1 truncate font-mono text-xs text-gray-800">{{ $apiKey->plain_secret }}</code>
                                            <button type="button" @click="copy(@js($apiKey->plain_secret), 'secret-{{ $apiKey->id }}')" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                                <span x-text="copying === 'secret-{{ $apiKey->id }}' ? 'Copiado' : 'Copiar'"></span>
                                            </button>
                                        @else
                                            <span class="text-xs text-amber-700">Secret antiguo no recuperable. Regenera para verlo.</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <form method="POST" action="{{ route('empresa.api-keys.toggle', $apiKey) }}" class="inline">
                                @csrf
                                <button type="submit" class="px-2 py-1 {{ $apiKey->active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }} rounded text-xs cursor-pointer">
                                    {{ $apiKey->active ? 'Activa' : 'Inactiva' }}
                                </button>
                            </form>
                        </td>
                        <td class="py-3 px-4 text-gray-500 text-xs">{{ optional($apiKey->last_used_at)->diffForHumans() ?? 'Nunca' }}</td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('empresa.api-keys.regenerate', $apiKey) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-orange-600 hover:text-orange-800 text-sm"
                                            onclick="return confirm('¿Regenerar el secret? El anterior dejará de funcionar.')">
                                        Regenerar
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('empresa.api-keys.destroy', $apiKey) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm"
                                            onclick="return confirm('¿Eliminar esta API Key?')">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500">No hay API Keys creadas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <h3 class="text-sm font-semibold text-blue-800 mb-2">¿Cómo usar la API?</h3>
        <p class="text-xs text-blue-600 mb-2">Incluye las credenciales en el header de tus peticiones:</p>
        <code class="block bg-blue-100 text-blue-800 text-xs p-2 rounded">
            X-Api-Key: {tu_api_key}<br>
            X-Api-Secret: {tu_api_secret}
        </code>
    </div>

    <x-modal id="create-api-key" title="Crear Nueva API Key">
        <form method="POST" action="{{ route('empresa.api-keys.store') }}">
            @csrf
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                <input type="text" name="name" id="name" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                       placeholder="Ej: API Producción">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition">
                Crear API Key
            </button>
        </form>
    </x-modal>
</div>
@endsection
