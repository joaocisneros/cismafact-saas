@php
    $editing = isset($user);

    // El contador es un rol de plataforma: no se asigna a ninguna empresa.
    $rolesSinEmpresa = $roles->whereIn('name', ['contador', 'super_admin'])->pluck('id')->values();
@endphp

<form action="{{ $editing ? route('super-admin.users.update', $user) : route('super-admin.users.store') }}" method="POST"
      data-success-message="{{ $editing ? 'Usuario actualizado correctamente.' : 'Usuario creado correctamente.' }}" class="p-5">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="grid gap-4 md:grid-cols-2"
         x-data="{
            rolesSinEmpresa: {{ Js::from($rolesSinEmpresa) }},
            rol: '{{ old('role_id', $user->role_id ?? ($roles->first()->id ?? '')) }}',
            get necesitaEmpresa() { return ! this.rolesSinEmpresa.includes(Number(this.rol)); }
         }">
        <label class="text-sm font-medium text-gray-700">Nombre
            <input name="name" value="{{ old('name', $user->name ?? '') }}" required
                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
        </label>
        <label class="text-sm font-medium text-gray-700">Correo
            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
        </label>
        <label class="text-sm font-medium text-gray-700">Empresa
            <select name="company_id" x-bind:required="necesitaEmpresa" x-bind:disabled="! necesitaEmpresa"
                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 disabled:bg-gray-100 disabled:text-gray-400">
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) old('company_id', $user->company_id ?? '') === (string) $company->id)>
                        {{ $company->razon_social }}
                    </option>
                @endforeach
            </select>
            <span x-show="! necesitaEmpresa" x-cloak class="mt-1 block text-xs font-normal text-gray-500">
                El contador trabaja sobre todas las empresas, no se asigna a ninguna.
            </span>
        </label>
        <label class="text-sm font-medium text-gray-700">Rol
            <select name="role_id" x-model="rol" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" @selected((string) old('role_id', $user->role_id ?? '') === (string) $role->id)>
                        {{ $role->display_name ?? $role->name }}
                    </option>
                @endforeach
            </select>
        </label>
        @unless($editing)
            <label class="text-sm font-medium text-gray-700">Contraseña
                <input type="password" name="password" minlength="8" required
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
            <label class="text-sm font-medium text-gray-700">Confirmar contraseña
                <input type="password" name="password_confirmation" minlength="8" required
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </label>
        @endunless
    </div>

    <div class="mt-5 flex justify-end gap-2">
        <button type="button" onclick="window.closeAdminModal()" class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cancelar</button>
        <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            {{ $editing ? 'Guardar cambios' : 'Crear usuario' }}
        </button>
    </div>
</form>
