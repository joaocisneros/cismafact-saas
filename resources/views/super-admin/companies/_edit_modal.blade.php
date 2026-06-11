<form action="{{ route('super-admin.companies.update', $company) }}" method="POST" enctype="multipart/form-data" data-success-message="Empresa actualizada correctamente." class="space-y-5 p-5">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">RUC</label>
            <input type="text" value="{{ $company->ruc }}" disabled
                   class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-500">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Razon Social</label>
            <input type="text" name="razon_social" value="{{ old('razon_social', $company->razon_social) }}" required
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Nombre Comercial</label>
            <input type="text" name="nombre_comercial" value="{{ old('nombre_comercial', $company->nombre_comercial) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Correo</label>
            <input type="email" name="email" value="{{ old('email', $company->email) }}" required
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Direccion</label>
            <input type="text" name="direccion" value="{{ old('direccion', $company->direccion) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Telefono</label>
            <input type="text" name="telefono" value="{{ old('telefono', $company->telefono) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
        </div>
        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700">Logo</label>
            <input type="file" name="logo" accept="image/*" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            @if($company->logo_path)
                <p class="mt-1 text-xs text-gray-500">Actual: {{ $company->logo_path }}</p>
            @endif
        </div>
    </div>

    <div class="flex justify-end gap-3 border-t border-gray-200 pt-4">
        <button type="button" onclick="window.closeAdminModal()"
                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            Cancelar
        </button>
        <button type="submit" class="rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            Guardar Cambios
        </button>
    </div>
</form>
