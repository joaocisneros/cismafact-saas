@php($editing = isset($plan))
<form method="POST" action="{{ $editing ? route('super-admin.plans.update', $plan) : route('super-admin.plans.store') }}"
      data-success-message="{{ $editing ? 'Plan actualizado correctamente.' : 'Plan creado correctamente.' }}" class="p-5">
    @if($editing) @method('PUT') @endif
    @include('super-admin.plans._form')
    <div class="mt-6 flex justify-end gap-3">
        <button type="button" onclick="window.closeAdminModal()" class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cancelar</button>
        <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white">
            {{ $editing ? 'Actualizar' : 'Guardar' }}
        </button>
    </div>
</form>
