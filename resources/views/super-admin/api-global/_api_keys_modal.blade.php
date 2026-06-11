<div class="p-5">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-gray-500">Últimas {{ $apiKeys->count() }} credenciales registradas.</p>
    </div>
    <div class="max-h-[60vh] overflow-auto border-y border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="sticky top-0 bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Empresa</th>
                    <th class="px-4 py-3">Key</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Último uso</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($apiKeys as $apiKey)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $apiKey->company->razon_social ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-500">{{ $apiKey->name }}</div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ \Illuminate\Support\Str::limit($apiKey->key, 24) }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $apiKey->active ? 'text-green-700' : 'text-red-700' }}">{{ $apiKey->active ? 'Activa' : 'Inactiva' }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $apiKey->last_used_at?->diffForHumans() ?? 'Nunca' }}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button"
                                    onclick="window.openAdminModal('{{ route('super-admin.api-global.show-key', $apiKey) }}', 'Detalle de API Key')"
                                    class="font-medium text-blue-600 hover:text-blue-800">Ver</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay API keys registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
