@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Editar Usuario</h1>
            <p class="text-gray-500 mt-1">{{ $user->name }}</p>
        </div>
        <a href="{{ route('super-admin.users.show', $user) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
            Volver
        </a>
    </div>

    <form action="{{ route('super-admin.users.update', $user) }}" method="POST" class="bg-white rounded-xl shadow-sm p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Empresa *</label>
                <select name="company_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id', $user->company_id) == $company->id ? 'selected' : '' }}>
                            {{ $company->razon_social }} ({{ $company->ruc }})
                        </option>
                    @endforeach
                </select>
                @error('company_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rol *</label>
                <select name="role_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                            {{ $role->display_name ?? $role->name }}
                        </option>
                    @endforeach
                </select>
                @error('role_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('super-admin.users.show', $user) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Guardar Cambios</button>
        </div>
    </form>
</div>
@endsection
