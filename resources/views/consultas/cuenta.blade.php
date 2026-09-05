@extends('layouts.consultas')

@section('title', 'Mi cuenta')

@section('content')
{{--
    Los dos formularios van en modales y no sueltos en la pantalla.

    Son cosas que se tocan una vez cada mucho: tenerlos siempre abiertos
    llenaba la pantalla de cajas de texto. Así se ve lo que hay, y el
    formulario aparece solo cuando se va a cambiar algo.
--}}
<div x-data="{ abierto: null }" @keydown.escape.window="abierto = null">

    <div class="mb-5">
        <h1 class="text-2xl font-semibold text-gray-900">Mi cuenta</h1>
        <p class="text-[15px] text-gray-600">Tus datos para entrar aquí. No tienen nada que ver con la API.</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3.5">
                <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400">Tus datos</h2>
                <button type="button" @click="abierto = 'datos'"
                        class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Editar
                </button>
            </div>
            <dl class="space-y-4 p-5">
                <div>
                    <dt class="text-sm text-gray-500">Nombre</dt>
                    <dd class="text-[15px] font-semibold text-gray-900">{{ $usuario->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Correo — con este entras</dt>
                    <dd class="text-[15px] font-semibold text-gray-900">{{ $usuario->email }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3.5">
                <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400">Contraseña</h2>
                <button type="button" @click="abierto = 'clave'"
                        class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cambiar
                </button>
            </div>
            <dl class="space-y-4 p-5">
                <div>
                    <dt class="text-sm text-gray-500">Contraseña</dt>
                    <dd class="font-mono text-[15px] font-semibold tracking-widest text-gray-900">••••••••••</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Última vez que entraste</dt>
                    <dd class="text-[15px] font-semibold text-gray-900">
                        {{ $usuario->last_login_at?->diffForHumans() ?? 'esta es la primera' }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="mt-4 rounded-xl bg-blue-50 px-5 py-4 text-[15px] text-gray-700">
        <strong class="text-gray-900">No las confundas:</strong> el correo y la contraseña son para entrar
        aquí. La clave y el secreto son para que tu programa llame a la API.
    </div>

    {{-- Modal: tus datos --}}
    <div x-show="abierto === 'datos'" x-cloak
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
        <div @click.outside="abierto = null" class="my-auto w-full max-w-md rounded-xl bg-white shadow-xl">
            <form method="POST" action="{{ route('consultas.cuenta.guardar') }}">
                @csrf
                @method('PUT')

                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">Editar tus datos</h3>
                    <button type="button" @click="abierto = null" class="text-2xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <div class="space-y-4 p-5">
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
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
                    <button type="button" @click="abierto = null"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Cancelar</button>
                    <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: contraseña --}}
    <div x-show="abierto === 'clave'" x-cloak
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
        <div @click.outside="abierto = null" class="my-auto w-full max-w-md rounded-xl bg-white shadow-xl">
            <form method="POST" action="{{ route('consultas.cuenta.clave') }}">
                @csrf
                @method('PUT')

                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">Cambiar contraseña</h3>
                    <button type="button" @click="abierto = null" class="text-2xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <div class="space-y-4 p-5">
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
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
                    <button type="button" @click="abierto = null"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Cancelar</button>
                    <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                        Cambiar contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
