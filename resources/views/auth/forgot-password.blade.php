@extends('layouts.guest')

@section('title', 'Recuperar Contraseña')

@section('content')
<h2 class="text-xl font-semibold text-gray-700 mb-2">Recuperar Contraseña</h2>
<p class="text-sm text-gray-500 mb-6">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>

@if(session('status'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-sm">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="mb-6">
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
        <input type="email"
               id="email"
               name="email"
               value="{{ old('email') }}"
               required
               autofocus
               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
               placeholder="admin@empresa.com">
    </div>

    <button type="submit"
            class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition font-medium">
        Enviar Enlace
    </button>
</form>

<div class="mt-6 text-center">
    <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-800">
        ← Volver al inicio de sesión
    </a>
</div>
@endsection
