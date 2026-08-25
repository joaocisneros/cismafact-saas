@extends('layouts.guest')

@section('title', 'Crear Cuenta')

@section('content')
@php
    // Un solo estilo para todos los campos, y borde rojo si ese campo falló.
    $campo = fn ($nombre) => 'w-full rounded-lg border px-4 py-2.5 outline-none transition focus:ring-2 focus:ring-blue-500 '
        . ($errors->has($nombre) ? 'border-red-400' : 'border-gray-300 focus:border-blue-500');
@endphp

<div class="mb-5">
    <h2 class="text-2xl font-bold text-gray-800">Crea tu cuenta</h2>
    <p class="mt-1 text-sm text-gray-500">Registra tu empresa y empieza a facturar hoy mismo.</p>
</div>

<form method="POST" action="{{ route('register.post') }}" x-data="{ show: false }" class="space-y-4">
    @csrf

    <div>
        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700">Tu nombre</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus maxlength="255"
               placeholder="Juan Pérez" class="{{ $campo('name') }}">
        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="razon_social" class="mb-1.5 block text-sm font-medium text-gray-700">Razón social</label>
        <input type="text" id="razon_social" name="razon_social" value="{{ old('razon_social') }}" required maxlength="255"
               placeholder="Mi Empresa S.A.C." class="{{ $campo('razon_social') }}">
        @error('razon_social')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="ruc" class="mb-1.5 block text-sm font-medium text-gray-700">RUC</label>
        <input type="text" id="ruc" name="ruc" value="{{ old('ruc') }}" required
               maxlength="11" inputmode="numeric" pattern="\d{11}"
               placeholder="20123456789" class="{{ $campo('ruc') }}">
        @error('ruc')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">Correo electrónico <span class="font-normal text-gray-400">— será tu usuario</span></label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required
               placeholder="tucorreo@empresa.com" class="{{ $campo('email') }}">
        @error('email')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Las dos contraseñas caben en una fila y usan el mismo componente que
         el resto del sistema, con su icono para mostrarlas. --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">Contraseña</label>
            <x-password-input name="password" required />
        </div>
        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700">Confirmar contraseña</label>
            <x-password-input name="password_confirmation" required placeholder="Repite la contraseña" />
        </div>
    </div>

    @error('password')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

    <button type="submit"
            class="mt-2 w-full rounded-lg bg-blue-600 py-2.5 font-medium text-white transition hover:bg-blue-700">
        Crear cuenta
    </button>

    {{-- El certificado y el paso a producción se configuran después, ya dentro.
         Preguntarlo en el registro espantaba: quien acaba de llegar no sabe qué es. --}}
    <p class="text-center text-xs text-gray-500">
        Empiezas en modo de pruebas, sin costo y sin certificado.
    </p>
</form>

<p class="mt-5 text-center text-sm text-gray-600">
    ¿Ya tienes cuenta?
    <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-800">Inicia sesión</a>
</p>
@endsection
