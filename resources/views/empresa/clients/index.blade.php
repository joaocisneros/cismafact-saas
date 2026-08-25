@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Clientes</h1>
            <p class="text-gray-500 mt-1">Registra y administra los clientes a quienes les emites comprobantes.</p>
        </div>
        <button type="button"
                onclick="window.openAdminModal('{{ route('empresa.clients.create') }}', 'Nuevo cliente')"
                class="px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">+ Nuevo cliente</button>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Buscar por razón social o número de documento"
               class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
        <button class="px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">Buscar</button>
    </form>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-5 py-3">Documento</th>
                    <th class="px-5 py-3">Razón Social / Nombre</th>
                    <th class="px-5 py-3">Teléfono</th>
                    <th class="px-5 py-3">Email</th>
                    <th class="px-5 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($clients as $client)
                    <tr>
                        <td class="px-5 py-4">
                            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $client->document_type_name }}</span>
                            <span class="ml-1 text-gray-800">{{ $client->numero_documento }}</span>
                        </td>
                        <td class="px-5 py-4 font-medium text-gray-900">{{ $client->razon_social }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $client->telefono ?? '—' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $client->email ?? '—' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-1.5">
                                <x-icon-action icon="editar" label="Editar cliente" color="blue" type="button"
                                               onclick="window.openAdminModal('{{ route('empresa.clients.edit', $client) }}', 'Editar {{ $client->razon_social }}')" />
                                <form method="POST" action="{{ route('empresa.clients.destroy', $client) }}"
                                      onsubmit="return confirm('¿Eliminar este cliente?')">
                                    @csrf @method('DELETE')
                                    <x-icon-action icon="eliminar" label="Eliminar cliente" color="red" />
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td class="px-5 py-8 text-center text-gray-500" colspan="5">No hay clientes registrados. Crea el primero para poder emitir.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $clients->links() }}</div>
</div>
@endsection
