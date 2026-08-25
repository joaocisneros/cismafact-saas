@extends('layouts.app')

@section('title', 'Mi Empresa')

@section('content')
<div class="space-y-5">
    <div>
        <h2 class="text-lg font-semibold text-gray-800">Datos de la Empresa</h2>
        <p class="mt-0.5 text-sm text-gray-500">Aparecen en tus comprobantes y en el PDF que recibe tu cliente.</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3">
            <p class="mb-1 text-sm font-semibold">No se pudo guardar. Revisa:</p>
            <ul class="list-inside list-disc space-y-0.5 text-sm">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- enctype: sin esto el navegador no envia el archivo y el logo no se sube. --}}
    <form method="POST" action="{{ route('empresa.company.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Dos columnas: el formulario a la izquierda y el resumen + logo a la
             derecha, para que no quede todo apilado en vertical. --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

            <div class="space-y-5 lg:col-span-2">
                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-md font-semibold text-gray-800">Datos del negocio</h3>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="razon_social" class="mb-1 block text-sm font-medium text-gray-700">Razón social *</label>
                            <input type="text" name="razon_social" id="razon_social" value="{{ old('razon_social', $company->razon_social) }}"
                                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="nombre_comercial" class="mb-1 block text-sm font-medium text-gray-700">Nombre comercial</label>
                            <input type="text" name="nombre_comercial" id="nombre_comercial" value="{{ old('nombre_comercial', $company->nombre_comercial) }}"
                                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="direccion" class="mb-1 block text-sm font-medium text-gray-700">Dirección fiscal</label>
                            <input type="text" name="direccion" id="direccion" value="{{ old('direccion', $company->direccion) }}"
                                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="telefono" class="mb-1 block text-sm font-medium text-gray-700">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" value="{{ old('telefono', $company->telefono) }}"
                                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Correo</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $company->email) }}"
                                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <label for="web" class="mb-1 block text-sm font-medium text-gray-700">Sitio web</label>
                            <input type="url" name="web" id="web" value="{{ old('web', $company->web) }}"
                                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="https://">
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-gray-500">Teléfono, correo y web son opcionales; se imprimen si los rellenas.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 font-medium text-white transition hover:bg-blue-700">
                        Guardar cambios
                    </button>
                    <a href="{{ route('empresa.dashboard') }}" class="rounded-lg bg-gray-100 px-6 py-2.5 text-gray-700 transition hover:bg-gray-200">
                        Cancelar
                    </a>
                </div>
            </div>

            <div class="space-y-5">
                <div class="rounded-xl bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $company->razon_social }}</p>
                            <p class="font-mono text-xs text-gray-500">RUC {{ $company->ruc }}</p>
                        </div>
                        @if($company->modo_produccion)
                            <span class="shrink-0 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">● Producción</span>
                        @else
                            <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">● Beta</span>
                        @endif
                    </div>
                    <p class="mt-3 border-t border-gray-100 pt-2.5 text-xs text-gray-500">
                        El RUC no se edita: identifica a tu empresa ante SUNAT.
                    </p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-800">Logo</h3>
                    <p class="mt-0.5 text-xs text-gray-500">Va en la cabecera del PDF. PNG o JPG.</p>

                    <div class="mt-3 flex h-28 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                        @if($company->logo_path)
                            <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo de la empresa" class="max-h-full max-w-full object-contain">
                        @else
                            <span class="text-xs text-gray-400">Sin logo</span>
                        @endif
                    </div>

                    <input type="file" name="logo" id="logo" accept="image/png,image/jpeg"
                           class="mt-3 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-blue-500">

                    <p class="mt-1.5 text-xs text-gray-500">
                        @if($company->logo_path)
                            Si no eliges ninguno, se mantiene el actual.
                        @endif
                        Usa una imagen pequeña (unos 300&times;300&nbsp;px): va incrustada en cada PDF.
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
