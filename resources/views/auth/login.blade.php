@extends('layouts.guest')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Bienvenido de nuevo</h2>
    <p class="text-sm text-gray-500 mt-1">Ingresa tus credenciales para acceder a tu panel.</p>
</div>

<form method="POST" action="{{ route('login.post') }}" x-data="{ show: false }">
    @csrf

    {{-- Aire entre los dos campos: pegados, la etiqueta "Contraseña" quedaba
         casi tocando la caja del correo. --}}
    <div class="mb-5">
        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">Correo electrónico</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
               placeholder="tucorreo@empresa.com"
               class="w-full rounded-lg border py-2.5 pl-4 pr-4 outline-none transition focus:ring-2 focus:ring-blue-500 {{ $errors->has('email') ? 'border-red-400' : 'border-gray-300 focus:border-blue-500' }}">
    </div>

    <div class="mb-5">
        <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">Contraseña</label>
        <x-password-input name="password" required :minlength="false" placeholder="Tu contraseña" />
    </div>

    <div class="mb-6 flex items-center justify-between">
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
