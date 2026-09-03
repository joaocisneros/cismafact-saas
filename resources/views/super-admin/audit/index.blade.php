@extends('layouts.app')

@section('title', 'Auditoría')

@section('content')
<div class="space-y-5">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Auditoría</h2>
        <p class="mt-1 text-sm text-gray-500">Registro de cambios realizados desde el panel administrativo.</p>
    </div>

    <form method="GET" class="grid gap-3 border-y border-gray-200 bg-white py-4 md:grid-cols-3 xl:grid-cols-6">
        <input name="search" value="{{ request('search') }}" placeholder="Buscar: secret, soporte, empresa…"
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
        <label class="flex items-center gap-2 text-sm text-gray-500">
            Desde
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </label>
        <label class="flex items-center gap-2 text-sm text-gray-500">
            Hasta
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </label>
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
                    <th class="px-4 py-3">Qué hizo</th>
                    <th class="px-4 py-3">Dónde</th>
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
                        {{-- La frase, no el nombre interno de la ruta: al revisar el
                             registro lo que hace falta saber es que paso. --}}
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ \App\Support\AccionAuditada::describir($log) }}</p>
                            @if(\App\Support\AccionAuditada::enSoporte($log))
                                <span class="mt-1 inline-block rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700"
                                      title="La acción se hizo desde el panel de la empresa, con una sesión de soporte abierta">
                                    en soporte · {{ \App\Support\AccionAuditada::quienEstabaDetras($log) }}
                                </span>
                            @endif
                        </td>

                        {{-- El nombre de la ruta se sigue enseñando aqui, en pequeño:
                             cuando hay que rastrear algo en el codigo es lo unico
                             que sirve. --}}
                        <td class="max-w-xs px-4 py-3" title="{{ $log->route_name }} · {{ $log->path }}">
                            <div class="truncate font-medium text-gray-800">{{ \App\Support\AccionAuditada::modulo($log) }}</div>
                            <div class="truncate text-xs text-gray-500">{{ \App\Support\AccionAuditada::panel($log) }}</div>
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
