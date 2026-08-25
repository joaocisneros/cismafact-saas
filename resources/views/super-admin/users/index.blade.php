@php
    // El contador da de alta y edita usuarios de empresa, pero no toca
    // cuentas de plataforma ni realiza acciones sobre cuentas ajenas
    // (contrasena, bloqueo, borrado). El servidor lo comprueba igual; esto
    // es para no ensenarle botones que le van a rebotar.
    $esSuperAdmin = auth()->user()->hasRole('super_admin');
@endphp

@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <h2 class="text-lg font-semibold text-gray-800">Usuarios del Sistema</h2>
        <button type="button" onclick="window.openAdminModal('{{ route('super-admin.users.create') }}', 'Crear usuario')"
                class="whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Crear Usuario</button>
    </div>

    <form method="GET" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Buscar</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nombre o email..."
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Empresa</label>
                <select name="company_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Todas las empresas</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                            {{ $company->razon_social }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Estado</label>
                <select name="status" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Todos</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activos</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="min-w-24 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Buscar</button>
                <a href="{{ route('super-admin.users.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Correo</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Empresa</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Rol</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Registro</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $user->company->razon_social ?? 'Plataforma' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-700">
                                {{ $user->role->display_name ?? $user->role->name ?? 'Sin rol' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($user->isLocked())
                                <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Bloqueado</span>
                            @else
                                <x-status-badge :status="$user->active ? 'active' : 'inactive'" />
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                <x-icon-action icon="ver" label="Ver detalle" color="blue" type="button"
                                               onclick="window.openAdminModal('{{ route('super-admin.users.show', $user) }}', 'Detalle de usuario')" />
                                @if($esSuperAdmin || ! in_array(optional($user->role)->name, ['super_admin', 'contador'], true))
                                    <x-icon-action icon="editar" label="Editar usuario" color="slate" type="button"
                                                   onclick="window.openAdminModal('{{ route('super-admin.users.edit', $user) }}', 'Editar usuario')" />
                                @endif
                                @if($esSuperAdmin && ! $user->hasRole('super_admin'))
                                    <x-icon-action icon="clave" label="Restablecer contraseña" color="violet" type="button"
                                                   onclick="window.openAdminModal('{{ route('super-admin.users.reset-password.form', $user) }}', 'Restablecer contraseña')" />
                                    <form method="POST" action="{{ route('super-admin.users.toggle-active', $user) }}">
                                        @csrf
                                        <x-icon-action :icon="$user->active ? 'suspender' : 'activar'"
                                                       :label="$user->active ? 'Suspender usuario' : 'Activar usuario'"
                                                       :color="$user->active ? 'amber' : 'emerald'" />
                                    </form>
                                    <form method="POST" action="{{ route('super-admin.users.destroy', $user) }}" onsubmit="return confirm('¿Eliminar este usuario?');">
                                        @csrf
                                        @method('DELETE')
                                        <x-icon-action icon="eliminar" label="Eliminar usuario" color="red" />
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500">No se encontraron usuarios.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-4 py-3">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
