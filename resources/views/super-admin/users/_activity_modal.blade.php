<div class="p-5">
    <div class="mb-4">
        <p class="font-medium text-gray-900">{{ $user->name }}</p>
        <p class="text-sm text-gray-500">{{ $user->email }}</p>
    </div>

    <div class="overflow-x-auto border-y border-gray-200">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">IP</th>
                    <th class="px-4 py-3">Resultado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($loginHistory as $log)
                    <tr>
                        <td class="px-4 py-3">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $log->ip_address ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $log->success ? 'text-green-700' : 'text-red-700' }}">
                                {{ $log->success ? 'Exitoso' : 'Fallido' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">Sin actividad registrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
