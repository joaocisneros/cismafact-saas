@extends('layouts.app')

@section('title', 'Mi Empresa')

@section('content')
<div class="space-y-6">
    <h2 class="text-lg font-semibold text-gray-800">Datos de la Empresa</h2>

    <form method="POST" action="{{ route('empresa.company.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-4">Información General</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="razon_social" class="block text-sm font-medium text-gray-700 mb-1">Razón Social *</label>
                    <input type="text" name="razon_social" id="razon_social"
                           value="{{ old('razon_social', $company->razon_social) }}"
                           required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label for="nombre_comercial" class="block text-sm font-medium text-gray-700 mb-1">Nombre Comercial</label>
                    <input type="text" name="nombre_comercial" id="nombre_comercial"
                           value="{{ old('nombre_comercial', $company->nombre_comercial) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label for="direccion" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <input type="text" name="direccion" id="direccion"
                           value="{{ old('direccion', $company->direccion) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" name="telefono" id="telefono"
                           value="{{ old('telefono', $company->telefono) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="email"
                           value="{{ old('email', $company->email) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label for="web" class="block text-sm font-medium text-gray-700 mb-1">Sitio Web</label>
                    <input type="url" name="web" id="web"
                           value="{{ old('web', $company->web) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                           placeholder="https://">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">RUC</label>
                    <input type="text" value="{{ $company->ruc }}" disabled
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                    <p class="text-xs text-gray-500 mt-1">El RUC no se puede modificar</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-4">Logo de la Empresa</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">Logo (imagen)</label>
                    <input type="file" name="logo" id="logo" accept="image/*"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    @if($company->logo_path)
                        <p class="text-xs text-green-600 mt-1">✓ Logo actual instalado</p>
                    @endif
                </div>
                <div>
                    @if($company->logo_path)
                        <div class="flex items-center gap-3">
                            <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                                <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo" class="w-full h-full object-contain">
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Logo actual</p>
                                <p class="text-xs text-gray-400">Subir nuevo reemplazará este</p>
                            </div>
                        </div>
                    @else
                        <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    @endif
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
