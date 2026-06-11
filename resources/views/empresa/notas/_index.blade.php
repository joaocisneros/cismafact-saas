<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $titulo }}</h1>
            <p class="text-gray-500 mt-1">Emite y consulta tus {{ strtolower($titulo) }} electrónicas.</p>
        </div>
        <a href="{{ route('empresa.' . $routePrefix . '.create') }}"
           class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            + Nueva nota
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <form method="GET" class="grid gap-3 bg-white border border-gray-200 rounded-lg p-4 md:grid-cols-4">
        <input name="search" value="{{ request('search') }}" placeholder="N° nota, doc. afectado o cliente"
               class="rounded-md border border-gray-300 px-3 py-2 text-sm md:col-span-2">
        <select name="status" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <option value="">Todos los estados</option>
            @foreach(['PENDIENTE','ACEPTADO','RECHAZADO','ANULADO'] as $st)
                <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst(strtolower($st)) }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white">Filtrar</button>
            <a href="{{ route('empresa.' . $routePrefix . '.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700">Limpiar</a>
        </div>
    </form>

    <div class="overflow-x-auto bg-white border border-gray-200 rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Nota</th>
                    <th class="px-4 py-3">Afecta a</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($notas as $n)
                    @php
                        $estadoClass = match($n->estado_sunat) {
                            'ACEPTADO' => 'bg-green-50 text-green-700',
                            'RECHAZADO' => 'bg-red-50 text-red-700',
                            'ANULADO' => 'bg-gray-100 text-gray-600',
                            default => 'bg-amber-50 text-amber-700',
                        };
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $n->serie }}-{{ $n->correlativo }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $n->num_doc_afectado }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $n->client?->razon_social ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $n->fecha_emision?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">{{ $n->moneda === 'USD' ? '$' : 'S/' }} {{ number_format((float) $n->mto_imp_venta, 2) }}</td>
                        <td class="px-4 py-3"><span class="rounded px-2 py-1 text-xs font-medium {{ $estadoClass }}">{{ $n->estado_sunat ?? 'PENDIENTE' }}</span></td>
                        <td class="px-4 py-3 text-right"><button type="button" onclick="openAdminModal('{{ route('empresa.' . $routePrefix . '.show', $n->id) }}', '{{ $titulo }} {{ $n->serie }}-{{ $n->correlativo }}')" class="text-sm font-medium text-blue-600 hover:text-blue-800">Ver</button></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">No hay {{ strtolower($titulo) }} emitidas todavía.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $notas->links() }}</div>
</div>
