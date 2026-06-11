<div class="p-5">
    <p class="mb-4 text-sm text-gray-500">Últimas {{ $logs->count() }} solicitudes registradas.</p>
    <div class="max-h-[60vh] overflow-auto border-y border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="sticky top-0 bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Empresa</th>
                    <th class="px-4 py-3">Solicitud</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Tiempo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $log->company->razon_social ?? 'N/A' }}</td>
                        <td class="max-w-xs px-4 py-3">
                            <span class="font-medium text-gray-900">{{ $log->method }}</span>
                            <span class="block truncate text-xs text-gray-500">{{ $log->path }}</span>
                        </td>
                        <td class="px-4 py-3 {{ $log->status_code >= 400 ? 'text-red-700' : 'text-green-700' }}">{{ $log->status_code }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $log->response_time_ms ? $log->response_time_ms.' ms' : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay logs registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
