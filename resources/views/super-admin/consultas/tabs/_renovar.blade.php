<template x-if="renovacion">
    <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4"
         @keydown.escape.window="renovacion = null">
        <div class="my-auto w-full max-w-lg overflow-hidden rounded-xl bg-white shadow-xl"
             @click.outside="renovacion = null"
             x-data="{
                 meses: 1,
                 nuevaFecha() {
                     const p = renovacion.base.split('-').map(Number);
                     const destino = p[1] - 1 + this.meses;
                     const ultimo = new Date(Date.UTC(p[0], destino + 1, 0)).getUTCDate();
                     const d = new Date(Date.UTC(p[0], destino, Math.min(p[2], ultimo)));
                     return d.toISOString().slice(0, 10);
                 },
                 fechaLegible() {
                     const [y, m, d] = this.nuevaFecha().split('-');
                     return `${d}/${m}/${y}`;
                 },
                 dinero(valor) {
                     return 'S/ ' + Number(valor || 0).toLocaleString('es-PE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                 }
             }">
            <form method="POST" :action="'{{ url('super-admin/consultas/llaves') }}/' + renovacion.id + '/renovar'">
                @csrf
                <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4">
                    <div class="min-w-0">
                        <h3 class="text-lg font-semibold text-gray-900">Renovar vigencia</h3>
                        <p class="mt-0.5 truncate text-sm text-gray-500" x-text="renovacion.titular"></p>
                    </div>
                    <button type="button" @click="renovacion = null" class="text-2xl leading-none text-gray-400 hover:text-gray-600" aria-label="Cerrar">&times;</button>
                </div>

                <div class="space-y-5 px-5 py-5">
                    <div class="grid grid-cols-2 gap-3 rounded-lg bg-gray-50 p-3 text-sm">
                        <div><span class="block text-xs text-gray-500">Entorno</span><strong x-text="renovacion.entorno"></strong></div>
                        <div><span class="block text-xs text-gray-500">Plan</span><strong x-text="renovacion.plan"></strong></div>
                        <div><span class="block text-xs text-gray-500">Vigencia actual</span><strong x-text="renovacion.expira"></strong></div>
                        <div><span class="block text-xs text-gray-500">Precio mensual</span><strong x-text="renovacion.a_medida ? 'A convenir' : dinero(renovacion.precio)"></strong></div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-900">Periodo de renovación</label>
                        <div class="grid grid-cols-4 gap-2">
                            <template x-for="opcion in [1, 3, 6, 12]" :key="opcion">
                                <label class="cursor-pointer rounded-lg border px-2 py-2.5 text-center text-sm font-medium transition"
                                       :class="meses === opcion ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-gray-300 text-gray-600 hover:bg-gray-50'">
                                    <input class="sr-only" type="radio" name="meses" :value="opcion" x-model.number="meses">
                                    <span x-text="opcion === 12 ? '1 año' : opcion + (opcion === 1 ? ' mes' : ' meses')"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-end justify-between gap-4 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3">
                        <div>
                            <span class="block text-xs text-blue-600">Nueva vigencia</span>
                            <strong class="text-base text-gray-900" x-text="fechaLegible()"></strong>
                        </div>
                        <div class="text-right">
                            <span class="block text-xs text-blue-600">Total</span>
                            <strong class="text-lg text-gray-900" x-text="renovacion.a_medida ? 'A convenir' : dinero(renovacion.precio * meses)"></strong>
                            <span class="block text-[11px] text-gray-500" x-show="!renovacion.a_medida" x-text="dinero(renovacion.precio) + ' × ' + meses"></span>
                        </div>
                    </div>

                    <p class="flex items-start gap-2 text-xs text-emerald-700">
                        <span class="font-bold">✓</span>
                        <span>Se conservarán la misma API Key y el mismo secreto. El cliente no tendrá que modificar su integración.</span>
                    </p>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-4">
                    <button type="button" @click="renovacion = null" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Confirmar renovación</button>
                </div>
            </form>
        </div>
    </div>
</template>
