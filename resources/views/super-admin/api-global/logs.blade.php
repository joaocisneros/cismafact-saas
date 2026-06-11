@extends('layouts.app')

@section('title', 'Logs de API')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Logs de API</h1>
            <p class="text-gray-500 mt-1">Historial completo de llamadas a la API</p>
        </div>
        <a href="{{ route('super-admin.api-global') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Volver</a>
    </div>

    <form method="GET" class="bg-white rounded-xl shadow-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Empresa</label>
                <select name="company_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Todas</option>
                    @foreach($companies as $c)
                        <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->razon_social }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status Code</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="200" {{ request('status') == '200' ? 'selected' : '' }}>200 OK</option>
                    <option value="400" {{ request('status') == '400' ? 'selected' : '' }}>400 Bad Request</option>
                    <option value="401" {{ request('status') == '401' ? 'selected' : '' }}>401 Unauthorized</option>
                    <option value="404" {{ request('status') == '404' ? 'selected' : '' }}>404 Not Found</option>
                    <option value="500" {{ request('status') == '500' ? 'selected' : '' }}>500 Server Error</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Filtrar</button>
                <a href="{{ route('super-admin.api-global.logs') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Fecha</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Empresa</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Método</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Endpoint</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Status</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Tiempo</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 text-gray-500">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="py-3 px-4">{{ $log->company->razon_social ?? 'N/A' }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">{{ $log->method }}</span>
                        </td>
                        <td class="py-3 px-4 text-gray-500 text-xs max-w-xs truncate">{{ $log->path }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 {{ $log->status_code >= 400 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }} rounded text-xs">
                                {{ $log->status_code }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-500">{{ $log->response_time_ms ? $log->response_time_ms . 'ms' : '-' }}</td>
                        <td class="py-3 px-4 text-gray-500 text-xs">{{ $log->ip_address ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500">No hay logs registrados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
