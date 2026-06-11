@extends('layouts.guest')

@section('title', 'Crear Cuenta')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Crea tu cuenta</h2>
    <p class="text-sm text-gray-500 mt-1">Registra tu empresa y empieza a facturar hoy mismo.</p>
</div>

<form method="POST" action="{{ route('register.post') }}" x-data="{ show: false, metodo: '{{ old('metodo_emision', 'cisma_fact') }}' }">
    @csrf

    <div class="mb-4">
        <label for="razon_social" class="block text-sm font-medium text-gray-700 mb-1">Razón Social</label>
        <input type="text"
               id="razon_social"
               name="razon_social"
               value="{{ old('razon_social') }}"
               required
               autofocus
               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
               placeholder="Empresa S.A.C.">
    </div>

    <div class="mb-4">
        <label for="ruc" class="block text-sm font-medium text-gray-700 mb-1">RUC</label>
        <input type="text"
               id="ruc"
               name="ruc"
               value="{{ old('ruc') }}"
               required
               maxlength="11"
               pattern="\d{11}"
               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
               placeholder="20123456789">
        <p class="text-xs text-gray-500 mt-1">11 dígitos del RUC</p>
    </div>

    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">¿Cómo vas a emitir?</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <label class="flex cursor-pointer items-start gap-2 rounded-lg border p-3 transition"
                   :class="metodo === 'cisma_fact' ? 'border-blue-500 bg-blue-50' : 'border-gray-300'">
                <input type="radio" name="metodo_emision" value="cisma_fact" x-model="metodo" class="mt-0.5">
                <span>
                    <span class="block text-sm font-medium text-gray-800">Con Cisma Fact</span>
                    <span class="block text-xs text-gray-500">Emites desde la plataforma (requiere certificado).</span>
                </span>
            </label>
            <label class="flex cursor-pointer items-start gap-2 rounded-lg border p-3 transition"
                   :class="metodo === 'sunat_manual' ? 'border-blue-500 bg-blue-50' : 'border-gray-300'">
                <input type="radio" name="metodo_emision" value="sunat_manual" x-model="metodo" class="mt-0.5">
                <span>
                    <span class="block text-sm font-medium text-gray-800">SUNAT Manual</span>
                    <span class="block text-xs text-gray-500">Emites en el portal de SUNAT.</span>
                </span>
            </label>
        </div>
        <p class="text-xs text-gray-500 mt-1">Puedes cambiarlo después en Configuración.</p>
    </div>

    <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
        <input type="email"
               id="email"
               name="email"
               value="{{ old('email') }}"
               required
               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
               placeholder="admin@empresa.com">
        <p class="text-xs text-gray-500 mt-1">Será tu usuario para iniciar sesión</p>
    </div>

    <div class="mb-4">
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
        <div class="relative">
            <input :type="show ? 'text' : 'password'"
                   id="password"
                   name="password"
                   required
                   placeholder="Mínimo 8 caracteres"
                   class="w-full px-4 py-2.5 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
            <button type="button" @click="show = !show" tabindex="-1"
                    class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 text-sm"
                    x-text="show ? 'Ocultar' : 'Ver'"></button>
        </div>
    </div>

    <div class="mb-6">
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Contraseña</label>
        <input :type="show ? 'text' : 'password'"
               id="password_confirmation"
               name="password_confirmation"
               required
               placeholder="Repite tu contraseña"
               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
    </div>

    <button type="submit"
            class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition font-medium">
        Crear Cuenta
    </button>
</form>

<div class="mt-6 text-center">
    <p class="text-sm text-gray-600">
        ¿Ya tienes cuenta?
        <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 font-medium">Inicia sesión</a>
    </p>
</div>
@endsection
