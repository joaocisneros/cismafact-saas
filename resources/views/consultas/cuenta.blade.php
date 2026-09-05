@extends('layouts.consultas')

@section('title', 'Mi cuenta')

@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-semibold text-gray-900">Mi cuenta</h1>
    <p class="text-[15px] text-gray-600">Tus datos para entrar aquí. No tienen nada que ver con la API.</p>
</div>

<div class="grid gap-4 lg:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-3.5">
            <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400">Datos</h2>
        </div>
        <form method="POST" action="{{ route('consultas.cuenta.guardar') }}" class="space-y-4 p-5">
            @csrf
            @method('PUT')

            <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-gray-600">Nombre</span>
                <input name="name" value="{{ old('name', $usuario->name) }}" required maxlength="120"
                       class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-[15px]">
            </label>

            <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-gray-600">Correo — con este entras</span>
                <input name="email" type="email" value="{{ old('email', $usuario->email) }}" required maxlength="150"
                       class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-[15px]">
            </label>

            <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                Guardar
            </button>
        </form>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-3.5">
            <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400">Contraseña</h2>
        </div>
        <form method="POST" action="{{ route('consultas.cuenta.clave') }}" class="space-y-4 p-5">
            @csrf
            @method('PUT')

            <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-gray-600">Contraseña actual</span>
                <input name="actual" type="password" required autocomplete="current-password"
                       class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-[15px]">
            </label>

            <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-gray-600">Contraseña nueva</span>
                <input name="nueva" type="password" required minlength="8" autocomplete="new-password"
                       placeholder="Al menos 8 caracteres"
                       class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-[15px]">
            </label>

            <label class="block">
                <span class="mb-1.5 block text-sm font-semibold text-gray-600">Repite la nueva</span>
                <input name="nueva_confirmation" type="password" required minlength="8" autocomplete="new-password"
                       class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-[15px]">
            </label>

            <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                Cambiar contraseña
            </button>
        </form>
    </div>
</div>

<div class="mt-4 rounded-xl bg-blue-50 px-5 py-4 text-[15px] text-gray-700">
    <strong class="text-gray-900">No las confundas:</strong> el correo y la contraseña son para entrar
    aquí. La clave y el secreto son para que tu programa llame a la API.
</div>
@endsection
