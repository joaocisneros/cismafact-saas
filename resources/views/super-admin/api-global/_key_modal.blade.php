<div class="space-y-5 p-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h4 class="text-lg font-semibold text-gray-900">{{ $apiKey->name }}</h4>
            <p class="text-sm text-gray-500">{{ $apiKey->company->razon_social ?? 'N/A' }}</p>
        </div>
        <span class="rounded px-2 py-1 text-xs font-medium {{ $apiKey->active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
            {{ $apiKey->active ? 'Activa' : 'Bloqueada' }}
        </span>
    </div>

    <dl class="grid gap-3 rounded-md bg-gray-50 p-4 text-sm md:grid-cols-2">
        <div>
            <dt class="text-gray-500">X-Api-Key</dt>
            <dd class="break-all font-mono text-xs font-medium">{{ $apiKey->key }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Total llamadas</dt>
            <dd class="font-semibold">{{ number_format($totalUsage) }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Último uso</dt>
            <dd class="font-medium">{{ $apiKey->last_used_at?->diffForHumans() ?? 'Nunca' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Creación</dt>
            <dd class="font-medium">{{ $apiKey->created_at->format('d/m/Y H:i') }}</dd>
        </div>
    </dl>

    <div>
        <h5 class="mb-2 text-sm font-semibold text-gray-800">Últimos usos</h5>
        <div class="max-h-64 divide-y divide-gray-100 overflow-y-auto border-y border-gray-200">
            @forelse($recentUsage as $usage)
                <div class="flex items-center justify-between gap-3 py-2 text-xs">
                    <span class="truncate text-gray-700">{{ $usage->method }} {{ $usage->path }}</span>
                    <span class="{{ $usage->status_code >= 400 ? 'text-red-700' : 'text-green-700' }}">{{ $usage->status_code }}</span>
                </div>
            @empty
                <p class="py-3 text-sm text-gray-500">No hay uso registrado.</p>
            @endforelse
        </div>
    </div>

    <div class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-gray-500">
            {{ $apiKey->active
                ? 'Al bloquearla, las solicitudes con esta credencial serán rechazadas inmediatamente.'
                : 'Al activarla, la empresa podrá volver a utilizar esta credencial.' }}
        </p>
        <form method="POST"
              action="{{ route('super-admin.api-global.toggle-key', $apiKey) }}"
              data-success-message="API Key {{ $apiKey->active ? 'bloqueada' : 'activada' }} correctamente."
              onsubmit="return confirm('¿{{ $apiKey->active ? 'Bloquear' : 'Activar' }} esta API Key?')">
            @csrf
            <button type="submit"
                    class="whitespace-nowrap rounded-md px-4 py-2 text-sm font-medium text-white {{ $apiKey->active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }}">
                {{ $apiKey->active ? 'Bloquear API Key' : 'Activar API Key' }}
            </button>
        </form>
    </div>
</div>
