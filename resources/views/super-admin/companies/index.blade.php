@extends('layouts.app')

@section('title', 'Empresas')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Empresas</h2>
            <p class="mt-1 text-sm text-gray-500">Listado rapido de empresas, planes, documentos y API keys.</p>
        </div>
        <button type="button" onclick="window.openAdminModal('{{ route('super-admin.companies.create') }}?modal=1', 'Crear empresa')" class="whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Crear Empresa</button>
    </div>

    <form method="GET" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">General</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="RUC, razon social o correo"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">RUC</label>
                <input type="text" name="ruc" value="{{ request('ruc') }}" placeholder="Buscar RUC"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Razon Social</label>
                <input type="text" name="razon_social" value="{{ request('razon_social') }}" placeholder="Buscar empresa"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Estado</label>
                <select name="status" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Todos</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activas</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Suspendidas</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="min-w-24 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Buscar</button>
                <a href="{{ route('super-admin.companies.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="px-4 py-3 text-left font-medium text-gray-500">RUC</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Razon Social</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Correo</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Plan</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Docs</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">API Keys</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Fecha Registro</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                        @php
                            $totalDocs = $company->invoices_count + $company->boletas_count + $company->credit_notes_count + $company->debit_notes_count + $company->dispatch_guides_count;
                        @endphp
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $company->ruc }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $company->razon_social }}</div>
                                <div class="max-w-[220px] truncate text-xs text-gray-400">{{ $company->nombre_comercial ?? $company->direccion ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $company->email ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-600/20">
                                    {{ $company->plan->name ?? 'Sin plan' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ number_format($totalDocs) }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ number_format($company->api_keys_count) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $company->activo ? 'bg-green-50 text-green-700 ring-1 ring-green-600/20' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20' }}">
                                    {{ $company->activo ? 'Activa' : 'Suspendida' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $company->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" onclick="window.openAdminModal('{{ route('super-admin.companies.show', $company) }}?modal=1', 'Detalle de empresa')" class="text-sm text-blue-600 hover:text-blue-800">Ver</button>
                                    <button type="button" onclick="window.openAdminModal('{{ route('super-admin.companies.edit', $company) }}?modal=1', 'Editar empresa')" class="text-sm text-gray-600 hover:text-gray-800">Editar</button>
                                    <form method="POST" action="{{ route('super-admin.companies.toggle-status', $company) }}">
                                        @csrf
                                        <button type="submit" class="text-sm {{ $company->activo ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' }}">
                                            {{ $company->activo ? 'Suspender' : 'Reactivar' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('super-admin.companies.destroy', $company) }}" onsubmit="return confirm('Eliminar empresa {{ $company->razon_social }}? Esta accion no se puede deshacer.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-gray-500">No se encontraron empresas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-4 py-3">
            {{ $companies->links() }}
        </div>
    </div>

</div>
@endsection
