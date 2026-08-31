@extends('layouts.app')

@section('title', $client->exists ? 'Editar cliente' : 'Nuevo cliente')

@section('content')
@php
    $tipos = ['1' => 'DNI', '6' => 'RUC', '4' => 'Carnet de Extranjería', '0' => 'Doc. Trib. No Dom. sin RUC'];
    $action = $client->exists ? route('empresa.clients.update', $client) : route('empresa.clients.store');
@endphp
<div class="space-y-6 max-w-3xl">
    <h1 class="text-2xl font-bold text-gray-800">{{ $client->exists ? 'Editar cliente' : 'Nuevo cliente' }}</h1>

    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="bg-white rounded-xl shadow-sm p-6 space-y-4"
          x-data="@include('empresa.clients._autocompletar')">
        @csrf
        @if($client->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de documento *</label>
                <select name="tipo_documento" x-ref="tipo"
                        @change="buscar($event.target.value, $refs.numero.value)"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    @foreach($tipos as $val => $label)
                        <option value="{{ $val }}" @selected(old('tipo_documento', $client->tipo_documento) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número de documento *</label>
                <input type="text" name="numero_documento" value="{{ old('numero_documento', $client->numero_documento) }}"
                       x-ref="numero" @input.debounce.400ms="buscar($refs.tipo.value, $event.target.value)"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                       placeholder="DNI: 8 dígitos / RUC: 11 dígitos">
                    <template x-if="aviso">
                        <p class="mt-1.5 text-xs"
                           :class="{ 'text-green-700': avisoTipo === 'ok', 'text-amber-700': avisoTipo === 'ojo', 'text-red-600': avisoTipo === 'error' }"
                           x-text="aviso"></p>
                    </template>
                    <p x-show="buscando" class="mt-1.5 text-xs text-gray-500">Consultando…</p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Razón social / Nombre completo *</label>
            <input type="text" name="razon_social" x-ref="razon" value="{{ old('razon_social', $client->razon_social) }}"
                   class="uppercase placeholder:normal-case w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre comercial</label>
                <input type="text" name="nombre_comercial" value="{{ old('nombre_comercial', $client->nombre_comercial) }}"
                       class="uppercase placeholder:normal-case w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                <input type="text" name="direccion" x-ref="direccion" value="{{ old('direccion', $client->direccion) }}"
                       class="uppercase placeholder:normal-case w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono', $client->telefono) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $client->email) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Guardar</button>
            <a href="{{ route('empresa.clients.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Cancelar</a>
        </div>
    </form>
</div>
@endsection
