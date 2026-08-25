{{--
    Formulario de perfil para abrir dentro del modal de la cabecera.
    Espera: $user y $rutaUpdate (nombre de la ruta que guarda).

    El envio lo intercepta submitAdminModal() del layout, que hace fetch y
    muestra los errores de validacion sin recargar la pagina.
--}}
<form method="POST" action="{{ route($rutaUpdate) }}" class="space-y-5 p-5">
    @csrf
    @method('PUT')

    <div>
        <h4 class="text-sm font-semibold text-gray-800">Tus datos</h4>
        <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label for="modal_name" class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
                <input type="text" name="name" id="modal_name" value="{{ old('name', $user->name) }}"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="modal_email" class="mb-1 block text-sm font-medium text-gray-700">Correo</label>
                <input type="email" name="email" id="modal_email" value="{{ old('email', $user->email) }}"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <div class="border-t border-gray-100 pt-5">
        <h4 class="text-sm font-semibold text-gray-800">Cambiar contraseña</h4>
        <p class="mt-0.5 text-xs text-gray-500">Déjalo vacío si no quieres cambiarla.</p>

        <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label for="modal_current_password" class="mb-1 block text-sm font-medium text-gray-700">Actual</label>
                <input type="password" name="current_password" id="modal_current_password" autocomplete="current-password"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="modal_password" class="mb-1 block text-sm font-medium text-gray-700">Nueva</label>
                <input type="password" name="password" id="modal_password" autocomplete="new-password"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="modal_password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Repite la nueva</label>
                <input type="password" name="password_confirmation" id="modal_password_confirmation" autocomplete="new-password"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 border-t border-gray-100 pt-5">
        <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 font-medium text-white transition hover:bg-blue-700">
            Guardar cambios
        </button>
        <button type="button" onclick="window.closeAdminModal()"
                class="rounded-lg bg-gray-100 px-6 py-2.5 text-gray-700 transition hover:bg-gray-200">
            Cancelar
        </button>
    </div>
</form>
