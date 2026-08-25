<form action="{{ route('super-admin.companies.store') }}" method="POST" data-success-message="Empresa creada correctamente." class="space-y-5 p-5">
    @csrf

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">RUC</label>
            <input type="text" name="ruc" required maxlength="11" pattern="[0-9]{11}" placeholder="11 digitos"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Razon Social</label>
            <input type="text" name="razon_social" required
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Correo admin</label>
            <input type="email" name="email" required
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Direccion</label>
            <input type="text" name="direccion" placeholder="Direccion fiscal"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Contrasena</label>
            <x-password-input name="password" required class="text-sm" />
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Confirmar contrasena</label>
            <x-password-input name="password_confirmation" required placeholder="Repite la contraseña" class="text-sm" />
        </div>
    </div>

    <div class="flex justify-end gap-3 border-t border-gray-200 pt-4">
        <button type="button" onclick="window.closeAdminModal()" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</button>
        <button type="submit" class="rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">Crear Empresa</button>
    </div>
</form>
