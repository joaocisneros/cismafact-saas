@extends('layouts.app')

@section('title', 'Usuario - ' . $user->name)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('super-admin.users.index') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">{{ $user->name }}</h1>
            <x-status-badge :status="$user->active ? 'active' : 'inactive'" />
            @if($user->locked_until && $user->locked_until->isFuture())
                <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">Bloqueado</span>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('super-admin.users.edit', $user) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Editar</a>
            <a href="{{ route('super-admin.users.activity', $user) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Actividad</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-stat-card title="Último Acceso" :value="$user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Nunca'" color="blue" />
        <x-stat-card title="Intentos Fallidos" :value="$user->failed_login_attempts" color="orange" />
        <x-stat-card title="Contraseña Cambio" :value="optional($user->password_changed_at)->diffForHumans() ?? 'Nunca'" color="green" />
        <x-stat-card title="2FA" :value="$user->two_factor_enabled ? 'Activo' : 'Inactivo'" color="purple" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-4">Datos del Usuario</h3>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Nombre:</dt>
                    <dd class="text-sm font-medium">{{ $user->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Email:</dt>
                    <dd class="text-sm font-medium">{{ $user->email }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Empresa:</dt>
                    <dd class="text-sm font-medium">{{ $user->company->razon_social ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Rol:</dt>
                    <dd class="text-sm font-medium">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">
                            {{ $user->role->display_name ?? $user->role->name ?? 'Sin rol' }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Estado:</dt>
                    <dd class="text-sm font-medium"><x-status-badge :status="$user->active ? 'active' : 'inactive'" /></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Bloqueado hasta:</dt>
                    <dd class="text-sm font-medium">
                        @if($user->locked_until && $user->locked_until->isFuture())
                            <span class="text-red-600">{{ $user->locked_until->format('d/m/Y H:i') }}</span>
                        @else
                            <span class="text-gray-400">No</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 text-sm">Creado:</dt>
                    <dd class="text-sm font-medium">{{ $user->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-4">Últimos Accesos</h3>
            @if($loginHistory->count() > 0)
                <div class="space-y-3">
                    @foreach($loginHistory->take(5) as $log)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 {{ $log->success ? 'bg-green-100' : 'bg-red-100' }} rounded-full flex items-center justify-center">
                                @if($log->success)
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</p>
                                <p class="text-xs text-gray-500">{{ $log->ip_address ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 {{ $log->success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} rounded text-xs">
                            {{ $log->success ? 'Exitoso' : 'Fallido' }}
                        </span>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm text-center py-4">No hay historial de accesos.</p>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-md font-semibold text-gray-800 mb-4">Acciones</h3>
        <div class="flex flex-wrap gap-3">
            @if(!$user->hasRole('super_admin'))
                <form method="POST" action="{{ route('super-admin.users.toggle-lock', $user) }}" onsubmit="return confirm('¿{{ $user->locked_until && $user->locked_until->isFuture() ? 'Desbloquear' : 'Bloquear' }} usuario?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 {{ $user->locked_until && $user->locked_until->isFuture() ? 'bg-green-600 hover:bg-green-700' : 'bg-yellow-600 hover:bg-yellow-700' }} text-white rounded-lg text-sm">
                        {{ $user->locked_until && $user->locked_until->isFuture() ? 'Desbloquear' : 'Bloquear' }}
                    </button>
                </form>

                <form method="POST" action="{{ route('super-admin.users.toggle-active', $user) }}" onsubmit="return confirm('¿{{ $user->active ? 'Desactivar' : 'Activar' }} usuario?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 {{ $user->active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg text-sm">
                        {{ $user->active ? 'Desactivar' : 'Activar' }}
                    </button>
                </form>
            @endif

            <button type="button" onclick="document.getElementById('resetPasswordModal').classList.remove('hidden')" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm">
                Restablecer Contraseña
            </button>
        </div>
    </div>
</div>

<!-- Modal Restablecer Contraseña -->
<div id="resetPasswordModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Restablecer Contraseña</h3>
        <form action="{{ route('super-admin.users.reset-password', $user) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nueva Contraseña</label>
                <input type="password" name="new_password" required minlength="8" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Contraseña</label>
                <input type="password" name="new_password_confirmation" required minlength="8" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('resetPasswordModal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">Restablecer</button>
            </div>
        </form>
    </div>
</div>
@endsection
