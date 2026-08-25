<form method="POST" action="{{ route('empresa.api-keys.store') }}" class="p-5"
      data-success-message="API Key creada.">
    @csrf

    <label class="block text-sm font-medium text-gray-700">Nombre de la credencial
        <input name="name" required maxlength="255" value="{{ old('name') }}"
               placeholder="Ej.: Tienda online, ERP contable, App de ventas"
               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
        <span class="mt-1 block text-xs font-normal text-gray-500">
            Sirve para reconocerla después. Usa una credencial distinta por cada sistema que conectes:
            así puedes desactivar uno sin afectar a los demás.
        </span>
    </label>

    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Al crearla obtendrás una <strong>API Key</strong> y un <strong>API Secret</strong>. Son como un usuario
        y una contraseña: quien los tenga puede emitir comprobantes en nombre de tu empresa. No los compartas
        ni los pongas en código que otros puedan ver.
    </div>

    <div class="mt-5 flex justify-end gap-2">
        <button type="button" onclick="window.closeAdminModal()"
                class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cancelar</button>
        <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            Crear API Key
        </button>
    </div>
</form>
