@extends('layouts.guest')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Bienvenido de nuevo</h2>
    <p class="text-sm text-gray-500 mt-1">Ingresa tus credenciales para acceder a tu panel.</p>
</div>

<form method="POST" action="{{ route('login.post') }}" x-data="{ show: false }">
    @csrf

    <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
        <input type="email"
               id="email"
               name="email"
               value="{{ old('email') }}"
               required
               autofocus
               placeholder="tucorreo@empresa.com"
               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
    </div>

    <div class="mb-4">
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
        <div class="relative">
            <input :type="show ? 'text' : 'password'"
                   id="password"
                   name="password"
                   required
                   placeholder="••••••••"
                   class="w-full px-4 py-2.5 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
            <button type="button" @click="show = !show" tabindex="-1"
                    class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 text-sm"
                    x-text="show ? 'Ocultar' : 'Ver'"></button>
        </div>
    </div>

    <div class="flex items-center justify-between mb-6">
        <label class="flex items-center">
            <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="ml-2 text-sm text-gray-600">Recordarme</span>
        </label>
        <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-800">
            ¿Olvidaste tu contraseña?
        </a>
    </div>

    <button type="submit"
            class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition font-medium">
        Iniciar Sesión
    </button>
</form>

<div class="mt-6 text-center">
    <p class="text-sm text-gray-600">
        ¿No tienes cuenta?
        <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-800 font-medium">Regístrate aquí</a>
    </p>
</div>
@endsection
