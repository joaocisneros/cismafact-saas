{{--
    Formulario de cliente para el modal. Sirve para alta y edicion.
    El envio lo intercepta submitAdminModal() del layout, que muestra los
    errores de validacion sin recargar la pagina.
--}}
@php
    $tipos = ['1' => 'DNI', '6' => 'RUC', '4' => 'Carnet de Extranjería', '0' => 'Doc. Trib. No Dom. sin RUC'];
    $accion = $client->exists ? route('empresa.clients.update', $client) : route('empresa.clients.store');
@endphp

<form method="POST" action="{{ $accion }}" class="space-y-4 p-5">
    @csrf
    @if($client->exists) @method('PUT') @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label for="c_tipo_documento" class="mb-1 block text-sm font-medium text-gray-700">Tipo de documento *</label>
            <select name="tipo_documento" id="c_tipo_documento"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
                @foreach($tipos as $val => $label)
                    <option value="{{ $val }}" @selected(old('tipo_documento', $client->tipo_documento) === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="c_numero_documento" class="mb-1 block text-sm font-medium text-gray-700">Número de documento *</label>
            <input type="text" name="numero_documento" id="c_numero_documento"
                   value="{{ old('numero_documento', $client->numero_documento) }}"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="DNI: 8 dígitos / RUC: 11 dígitos">
        </div>
    </div>

    <div>
        <label for="c_razon_social" class="mb-1 block text-sm font-medium text-gray-700">Razón social / Nombre completo *</label>
        <input type="text" name="razon_social" id="c_razon_social" value="{{ old('razon_social', $client->razon_social) }}"
               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label for="c_nombre_comercial" class="mb-1 block text-sm font-medium text-gray-700">Nombre comercial</label>
            <input type="text" name="nombre_comercial" id="c_nombre_comercial"
                   value="{{ old('nombre_comercial', $client->nombre_comercial) }}"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label for="c_direccion" class="mb-1 block text-sm font-medium text-gray-700">Dirección</label>
            <input type="text" name="direccion" id="c_direccion" value="{{ old('direccion', $client->direccion) }}"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label for="c_telefono" class="mb-1 block text-sm font-medium text-gray-700">Teléfono</label>
            <input type="text" name="telefono" id="c_telefono" value="{{ old('telefono', $client->telefono) }}"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label for="c_email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="c_email" value="{{ old('email', $client->email) }}"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <div class="flex flex-wrap gap-3 border-t border-gray-100 pt-4">
        <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 font-medium text-white transition hover:bg-blue-700">
            {{ $client->exists ? 'Guardar cambios' : 'Crear cliente' }}
        </button>
        <button type="button" onclick="window.closeAdminModal()"
                class="rounded-lg bg-gray-100 px-6 py-2.5 text-gray-700 transition hover:bg-gray-200">
            Cancelar
        </button>
    </div>
</form>
