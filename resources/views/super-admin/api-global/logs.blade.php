@extends('layouts.app')

@section('title', 'Registro de llamadas')

@section('content')
@php
    // Un numero de estado HTTP no le dice nada a nadie fuera de programacion.
    // Se traduce a lo que realmente ocurrio, que es lo que se necesita para
    // saber si hay que hacer algo.
    $resultados = [
        200 => ['Correcto', 'ok'],
        201 => ['Creado', 'ok'],
        400 => ['Petición mal formada', 'error'],
        401 => ['Credenciales inválidas', 'error'],
        403 => ['Sin permiso o acceso cortado', 'error'],
        404 => ['No encontrado', 'error'],
        422 => ['Datos incompletos o inválidos', 'aviso'],
        429 => ['Demasiadas peticiones', 'aviso'],
        500 => ['Error del servidor', 'error'],
        503 => ['Servicio no disponible', 'error'],
    ];
@endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Registro de llamadas</h1>
            <p class="mt-1 text-gray-500">Cada petición que las empresas hicieron a tu API, y cómo terminó.</p>
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
                <label class="mb-1 block text-sm font-medium text-gray-700">Resultado</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    @foreach($resultados as $codigo => $r)
                        <option value="{{ $codigo }}" @selected(request('status') == $codigo)>{{ $r[0] }} ({{ $codigo }})</option>
                    @endforeach
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
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Petición</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Resultado</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">Tiempo</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-500">IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 text-gray-500">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="py-3 px-4">{{ $log->company->razon_social ?? 'N/A' }}</td>
                        <td class="max-w-xs truncate px-4 py-3">
                            <span class="font-mono text-xs text-gray-500">{{ $log->method }}</span>
                            <span class="font-mono text-xs text-gray-700">{{ $log->path }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                [$texto, $tipo] = $resultados[$log->status_code] ?? ['Respuesta ' . $log->status_code, $log->status_code >= 400 ? 'error' : 'ok'];
                                $color = match($tipo) {
                                    'ok' => 'bg-green-50 text-green-700',
                                    'aviso' => 'bg-amber-50 text-amber-700',
                                    default => 'bg-red-50 text-red-700',
                                };
                            @endphp
                            <span class="rounded px-2 py-1 text-xs font-medium {{ $color }}" title="Código HTTP {{ $log->status_code }}">
                                {{ $texto }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-500">{{ $log->response_time_ms ? $log->response_time_ms . 'ms' : '-' }}</td>
                        <td class="py-3 px-4 text-gray-500 text-xs">{{ $log->ip_address ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-500">No hay llamadas registradas con estos filtros.</td>
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
