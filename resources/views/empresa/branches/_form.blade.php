{{--
    Formulario de sucursal para el modal. Sirve para alta y edicion: cambia solo
    la ruta y el metodo. El envio lo intercepta submitAdminModal() del layout.
--}}
@php $esNueva = ! $branch->exists; @endphp

<form method="POST" action="{{ $accion }}" class="space-y-5 p-5">
    @csrf
    @if($metodo !== 'POST')
        @method($metodo)
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label for="f_codigo" class="mb-1 block text-sm font-medium text-gray-700">Código de establecimiento</label>
            @if($esNueva)
                <input type="text" name="codigo" id="f_codigo" maxlength="10" value="{{ old('codigo') }}"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="0001">
            @else
                {{-- El codigo no se edita: va en los comprobantes ya emitidos. --}}
                <input type="text" value="{{ $branch->codigo }}" disabled
                       class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-gray-500">
                <p class="mt-1 text-xs text-gray-500">No se puede cambiar: ya va en los comprobantes emitidos.</p>
            @endif
        </div>

        <div class="md:col-span-2">
            <label for="f_nombre" class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
            <input type="text" name="nombre" id="f_nombre" maxlength="255" value="{{ old('nombre', $branch->nombre) }}"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="Tienda Miraflores">
        </div>
    </div>

    @if($esNueva)
        <details class="rounded-lg bg-blue-50 p-3">
            <summary class="cursor-pointer text-xs font-medium text-blue-800">¿De dónde saco el código?</summary>
            <p class="mt-2 text-xs leading-relaxed text-blue-900">
                Lo asigna SUNAT al declarar el local. Lo encuentras en tu <strong>Ficha RUC</strong>:
                entra con tu Clave SOL → <em>Mi RUC y Otros Registros</em> → <em>Mis datos del RUC</em> →
                <em>Mi RUC</em> → <em>Ficha RUC</em>. La casa matriz suele ser <strong>0000</strong>.<br>
                Si el local todavía no está declarado, dalo de alta con el <strong>Formulario 2046</strong>.
            </p>
        </details>
    @endif

    <div>
        <label for="f_direccion" class="mb-1 block text-sm font-medium text-gray-700">Dirección</label>
        <input type="text" name="direccion" id="f_direccion" maxlength="255" value="{{ old('direccion', $branch->direccion) }}"
               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label for="f_ubigeo" class="mb-1 block text-sm font-medium text-gray-700">Ubigeo</label>
            <input type="text" name="ubigeo" id="f_ubigeo" maxlength="6" value="{{ old('ubigeo', $branch->ubigeo ?: '150101') }}"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label for="f_distrito" class="mb-1 block text-sm font-medium text-gray-700">Distrito</label>
            <input type="text" name="distrito" id="f_distrito" maxlength="255" value="{{ old('distrito', $branch->distrito) }}"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label for="f_provincia" class="mb-1 block text-sm font-medium text-gray-700">Provincia</label>
            <input type="text" name="provincia" id="f_provincia" maxlength="255" value="{{ old('provincia', $branch->provincia) }}"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label for="f_departamento" class="mb-1 block text-sm font-medium text-gray-700">Departamento</label>
            <input type="text" name="departamento" id="f_departamento" maxlength="255" value="{{ old('departamento', $branch->departamento) }}"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label for="f_telefono" class="mb-1 block text-sm font-medium text-gray-700">Teléfono <span class="text-xs text-gray-400">— opcional</span></label>
            <input type="text" name="telefono" id="f_telefono" maxlength="255" value="{{ old('telefono', $branch->telefono) }}"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label for="f_email" class="mb-1 block text-sm font-medium text-gray-700">Correo <span class="text-xs text-gray-400">— opcional</span></label>
            <input type="email" name="email" id="f_email" maxlength="255" value="{{ old('email', $branch->email) }}"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <div class="flex flex-wrap gap-3 border-t border-gray-100 pt-5">
        <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 font-medium text-white transition hover:bg-blue-700">
            {{ $esNueva ? 'Crear sucursal' : 'Guardar cambios' }}
        </button>
        <button type="button" onclick="window.closeAdminModal()"
                class="rounded-lg bg-gray-100 px-6 py-2.5 text-gray-700 transition hover:bg-gray-200">
            Cancelar
        </button>
    </div>
</form>
