@extends('layouts.app')

@section('title', 'Mi Perfil')

@section('content')
<div class="space-y-6">
    <h2 class="text-lg font-semibold text-gray-800">Mi Perfil</h2>

    <form method="POST" action="{{ route('empresa.profile.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-4">Información Personal</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" name="name" id="name"
                           value="{{ old('name', $user->name) }}"
                           required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico *</label>
                    <input type="email" name="email" id="email"
                           value="{{ old('email', $user->email) }}"
                           required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Registro</label>
                    <input type="text" value="{{ $user->created_at->format('d/m/Y H:i') }}" disabled
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Último Acceso</label>
                    <input type="text" value="{{ optional($user->last_login_at)?->format('d/m/Y H:i') ?? 'Nunca' }}" disabled
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-4">Cambiar Contraseña</h3>
            <p class="text-sm text-gray-500 mb-4">Deja en blanco si no deseas cambiar la contraseña.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña Actual</label>
                    <input type="password" name="current_password" id="current_password"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Nueva Contraseña</label>
                    <input type="password" name="password" id="password"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                Guardar Cambios
            </button>
            <a href="{{ route('empresa.dashboard') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
