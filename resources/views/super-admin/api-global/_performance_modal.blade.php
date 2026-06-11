<div class="space-y-5 p-5">
    <dl class="grid grid-cols-3 divide-x divide-gray-200 border-y border-gray-200">
        <div class="p-4"><dt class="text-xs uppercase text-gray-500">Disponibilidad</dt><dd class="mt-1 text-xl font-semibold text-green-700">{{ $uptime }}%</dd></div>
        <div class="p-4"><dt class="text-xs uppercase text-gray-500">Promedio</dt><dd class="mt-1 text-xl font-semibold text-gray-900">{{ number_format($avgResponseTime, 0) }} ms</dd></div>
        <div class="p-4"><dt class="text-xs uppercase text-gray-500">Solicitudes 30 días</dt><dd class="mt-1 text-xl font-semibold text-gray-900">{{ number_format($totalRequests) }}</dd></div>
    </dl>

    <div>
        <h4 class="mb-2 text-sm font-semibold text-gray-900">Últimos 7 días</h4>
        <div class="overflow-x-auto border-y border-gray-200">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr><th class="px-4 py-3">Fecha</th><th class="px-4 py-3">Solicitudes</th><th class="px-4 py-3">Promedio</th><th class="px-4 py-3">Máximo</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($dailyPerformance as $day)
                        <tr><td class="px-4 py-3">{{ $day['fecha'] }}</td><td class="px-4 py-3">{{ number_format($day['total']) }}</td><td class="px-4 py-3">{{ number_format($day['avg_time'], 0) }} ms</td><td class="px-4 py-3">{{ number_format($day['max_time'], 0) }} ms</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <h4 class="mb-2 text-sm font-semibold text-gray-900">Endpoints más usados</h4>
        <div class="max-h-56 overflow-auto border-y border-gray-200">
            @forelse($topEndpoints as $endpoint)
                <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-4 py-3 text-sm">
                    <span class="truncate text-gray-700">{{ $endpoint->path }}</span>
                    <span class="whitespace-nowrap text-gray-500">{{ number_format($endpoint->total) }} · {{ number_format($endpoint->avg_time, 0) }} ms</span>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-sm text-gray-500">Sin actividad registrada.</p>
            @endforelse
        </div>
    </div>
</div>
