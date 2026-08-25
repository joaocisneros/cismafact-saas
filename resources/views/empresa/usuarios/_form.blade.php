@php
    $editando = isset($usuario) && $usuario;
@endphp

<form method="POST" class="p-5"
      action="{{ $editando ? route('empresa.usuarios.update', $usuario) : route('empresa.usuarios.store') }}"
      data-success-message="{{ $editando ? 'Usuario actualizado.' : 'Usuario creado.' }}">
    @csrf
    @if($editando) @method('PUT') @endif

    <div class="grid gap-4 md:grid-cols-2">
        <label class="text-sm font-medium text-gray-700">Nombre
            <input name="name" value="{{ old('name', $usuario->name ?? '') }}" required maxlength="255"
                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
        </label>
        <label class="text-sm font-medium text-gray-700">Correo
            <input type="email" name="email" value="{{ old('email', $usuario->email ?? '') }}" required maxlength="255"
                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
        </label>

        <label class="text-sm font-medium text-gray-700 md:col-span-2">Rol
            <select name="rol" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                @foreach($roles as $rol)
                    <option value="{{ $rol->name }}"
                        @selected(old('rol', optional($usuario?->role)->name ?? 'company_user') === $rol->name)>
                        {{ $rol->name === 'company_admin' ? 'Administrador — acceso total a la empresa' : 'Empleado — solo emitir y consultar' }}
                    </option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs font-normal text-gray-500">
                El empleado no ve Datos de la Empresa, Configuración SUNAT, API Keys, Mi Plan ni Usuarios.
            </span>
        </label>

        <label class="text-sm font-medium text-gray-700">
            {{ $editando ? 'Nueva contraseña (opcional)' : 'Contraseña' }}
            <x-password-input name="password" :required="! $editando" class="mt-1" />
        </label>
        <label class="text-sm font-medium text-gray-700">Confirmar contraseña
            <x-password-input name="password_confirmation" :required="! $editando" placeholder="Repite la contraseña" class="mt-1" />
        </label>
    </div>

    <div class="mt-5 flex justify-end gap-2">
        <button type="button" onclick="window.closeAdminModal()"
                class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cancelar</button>
        <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            {{ $editando ? 'Guardar cambios' : 'Crear usuario' }}
        </button>
    </div>
</form>
