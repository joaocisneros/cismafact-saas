<form method="POST" action="{{ route('super-admin.users.reset-password', $user) }}" data-success-message="Contraseña actualizada correctamente." class="space-y-5 p-5">
    @csrf

    <div class="rounded-md bg-gray-50 p-4">
        <p class="font-medium text-gray-900">{{ $user->name }}</p>
        <p class="text-sm text-gray-500">{{ $user->email }}</p>
    </div>

    <label class="block text-sm font-medium text-gray-700">
        Nueva contraseña
        <x-password-input name="new_password" required autocomplete="new-password" class="mt-1" />
    </label>

    <label class="block text-sm font-medium text-gray-700">
        Confirmar contraseña
        <x-password-input name="new_password_confirmation" required autocomplete="new-password" placeholder="Repite la contraseña" class="mt-1" />
    </label>

    <p class="text-xs text-gray-500">Al guardar también se eliminarán el bloqueo y los intentos fallidos.</p>

    <div class="flex justify-end gap-2 border-t border-gray-200 pt-4">
        <button type="button" onclick="window.closeAdminModal()" class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cancelar</button>
        <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Restablecer</button>
    </div>
</form>
