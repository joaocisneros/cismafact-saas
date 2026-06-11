@extends('layouts.guest')

@section('title', 'Restablecer Contraseña')

@section('content')
<h2 class="text-xl font-semibold text-gray-700 mb-2">Restablecer Contraseña</h2>
<p class="text-sm text-gray-500 mb-6">Ingresa tu nueva contraseña.</p>

<form method="POST" action="{{ route('password.update') }}">
    @csrf

    <input type="hidden" name="token" value="{{ $token }}">

    <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
        <input type="email"
               id="email"
               name="email"
               value="{{ $email ?? old('email') }}"
               required
               autofocus
               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
    </div>

    <div class="mb-4">
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Nueva Contraseña</label>
        <input type="password"
               id="password"
               name="password"
               required
               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
    </div>

    <div class="mb-6">
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Contraseña</label>
        <input type="password"
               id="password_confirmation"
               name="password_confirmation"
               required
               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
    </div>

    <button type="submit"
            class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition font-medium">
        Restablecer Contraseña
    </button>
</form>
@endsection
