@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Usuarios</h1>
            <p class="mt-1 text-sm text-gray-500">
                El equipo que puede entrar a tu empresa. Cada persona con su propia cuenta.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <div class="text-right">
                <p class="text-xs text-gray-500">Plan {{ $cupo['plan'] ?? '—' }}</p>
                <p class="text-sm font-semibold {{ $cupo['lleno'] ? 'text-amber-600' : 'text-gray-800' }}">
                    {{ $cupo['usados'] }} / {{ $cupo['limite'] ?? '∞' }} usuarios
                </p>
            </div>

            @if($cupo['lleno'])
                <span class="cursor-not-allowed rounded-lg bg-gray-200 px-4 py-2 text-sm text-gray-500"
                      title="Alcanzaste el límite de usuarios de tu plan">Crear usuario</span>
            @else
                <button type="button"
                        onclick="window.openAdminModal('{{ route('empresa.usuarios.create') }}?modal=1', 'Nuevo usuario')"
                        class="whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                    Crear usuario
                </button>
            @endif
        </div>
    </div>

    @if($cupo['lleno'])
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Tu plan {{ $cupo['plan'] }} permite {{ $cupo['limite'] }} usuario(s) y ya los tienes en uso.
            Desactiva uno o cambia de plan para agregar más.
            <a href="{{ route('empresa.plan.index') }}" class="font-medium underline">Ver mi plan</a>
        </div>
    @endif

    <form method="GET" class="flex gap-2">
        <input name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o correo"
               class="w-full max-w-sm rounded-md border border-gray-300 px-3 py-2 text-sm">
        <button class="rounded-md bg-gray-800 px-4 py-2 text-sm text-white">Buscar</button>
        @if(request('search'))
            <a href="{{ route('empresa.usuarios.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700">Limpiar</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Correo</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Rol</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($usuarios as $u)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ $u->name }}
                                @if($u->id === auth()->id())
                                    <span class="ml-1 rounded bg-blue-50 px-1.5 py-0.5 text-[11px] text-blue-700">tú</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $u->email }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ optional($u->role)->name === 'company_admin' ? 'Administrador' : 'Empleado' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $u->active ? 'bg-green-50 text-green-700 ring-1 ring-green-600/20' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20' }}">
                                    {{ $u->active ? 'Activo' : 'Desactivado' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($u->id === auth()->id())
                                    <span class="text-xs text-gray-400">Tu cuenta se edita en «Mi perfil»</span>
                                @else
                                    <div class="flex items-center gap-1.5">
                                        <x-icon-action icon="editar" label="Editar usuario" color="slate" type="button"
                                                       onclick="window.openAdminModal('{{ route('empresa.usuarios.edit', $u) }}?modal=1', 'Editar usuario')" />
                                        <form method="POST" action="{{ route('empresa.usuarios.toggle-active', $u) }}">
                                            @csrf
                                            <x-icon-action :icon="$u->active ? 'suspender' : 'activar'"
                                                           :label="$u->active ? 'Desactivar cuenta' : 'Activar cuenta'"
                                                           :color="$u->active ? 'amber' : 'emerald'" />
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Aún no tienes otros usuarios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $usuarios->links() }}
</div>
@endsection
