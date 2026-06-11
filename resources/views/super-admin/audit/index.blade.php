@extends('layouts.app')

@section('title', 'Auditoría')

@section('content')
<div class="space-y-5">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Auditoría</h2>
        <p class="mt-1 text-sm text-gray-500">Registro de cambios realizados desde el panel administrativo.</p>
    </div>

    <form method="GET" class="grid gap-3 border-y border-gray-200 bg-white py-4 md:grid-cols-3 xl:grid-cols-6">
        <input name="search" value="{{ request('search') }}" placeholder="Ruta o acción"
               class="rounded-md border border-gray-300 px-3 py-2 text-sm">
        <select name="user_id" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <option value="">Todos los usuarios</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        <select name="company_id" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <option value="">Todas las empresas</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->razon_social }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
        <div class="flex gap-2">
            <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white">Filtrar</button>
            <a href="{{ route('super-admin.audit.index') }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm">Limpiar</a>
        </div>
    </form>

    <div class="overflow-x-auto border-y border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Usuario</th>
                    <th class="px-4 py-3">Acción</th>
                    <th class="px-4 py-3">Ruta</th>
                    <th class="px-4 py-3">Empresa</th>
                    <th class="px-4 py-3">Resultado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $log->user->name ?? 'Sistema' }}</div>
                            <div class="text-xs text-gray-500">{{ $log->ip_address }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">{{ strtoupper($log->method) }}</span>
                            <span class="ml-1 text-gray-600">{{ $log->action }}</span>
                        </td>
                        <td class="max-w-xs px-4 py-3">
                            <div class="truncate font-medium text-gray-800">{{ $log->route_name ?? $log->path }}</div>
                            <div class="truncate text-xs text-gray-500">{{ $log->path }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $log->company->razon_social ?? 'General' }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ ($log->response_status ?? 500) < 400 ? 'text-green-700' : 'text-red-700' }}">
                                HTTP {{ $log->response_status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">Aún no hay acciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $logs->links() }}</div>
</div>
@endsection
