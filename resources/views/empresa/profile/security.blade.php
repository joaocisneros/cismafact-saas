@extends('layouts.app')

@section('title', 'Seguridad')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Seguridad</h1>
            <p class="text-gray-500 mt-1">Configura la seguridad de tu cuenta</p>
        </div>
        <a href="{{ route('empresa.profile.edit') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
            Volver al Perfil
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Autenticación de Dos Factores (2FA)</h2>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-700">Estado actual:
                    <span class="font-semibold {{ $user->two_factor_enabled ? 'text-green-600' : 'text-red-600' }}">
                        {{ $user->two_factor_enabled ? 'Activada' : 'Desactivada' }}
                    </span>
                </p>
                <p class="text-sm text-gray-500 mt-1">Agrega una capa extra de seguridad a tu cuenta.</p>
            </div>
            <form action="{{ route('empresa.profile.two-factor') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 {{ $user->two_factor_enabled ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg text-sm">
                    {{ $user->two_factor_enabled ? 'Desactivar 2FA' : 'Activar 2FA' }}
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Historial de Accesos</h2>
        @if($loginHistory->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="pb-3 font-medium">Fecha/Hora</th>
                            <th class="pb-3 font-medium">IP</th>
                            <th class="pb-3 font-medium">Navegador</th>
                            <th class="pb-3 font-medium">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loginHistory as $log)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td class="py-3 text-gray-500">{{ $log->ip_address ?? 'N/A' }}</td>
                            <td class="py-3 text-gray-500 text-xs max-w-xs truncate">{{ $log->user_agent ?? 'N/A' }}</td>
                            <td class="py-3">
                                <span class="px-2 py-1 {{ $log->success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} rounded text-xs">
                                    {{ $log->success ? 'Exitoso' : 'Fallido' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 text-sm text-center py-4">No hay historial de accesos registrado.</p>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Información de la Cuenta</h2>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Email:</span>
                <span class="text-gray-800">{{ $user->email }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Último cambio de contraseña:</span>
                <span class="text-gray-800">{{ $user->password_changed_at ? \Carbon\Carbon::parse($user->password_changed_at)->format('d/m/Y H:i') : 'Nunca' }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
