{{-- Las consultas que se ofrecen. Una tabla y no tarjetas: aqui se viene a
     encender, apagar y comparar, y para eso una fila por servicio con sus
     columnas alineadas se lee mejor que cajas sueltas. --}}
<div class="space-y-5" x-data="{ nueva: false, editando: null }">

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Consultas que ofreces</h2>
                <p class="mt-0.5 text-xs text-gray-500">
                    Apagar una la deja fuera de servicio al instante, sin desplegar nada.
                </p>
            </div>
            <button type="button" @click="nueva = !nueva"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                <span x-show="!nueva">Nueva consulta</span>
                <span x-show="nueva" x-cloak>Cancelar</span>
            </button>
        </div>

        <div x-show="nueva" x-cloak class="border-b border-gray-200 bg-gray-50 px-5 py-4">
            <form method="POST" action="{{ route('super-admin.consultas.apis.guardar') }}"
                  class="grid gap-3 sm:grid-cols-3">
                @csrf
                <div>
                    <label for="nombre" class="mb-1 block text-xs font-medium text-gray-700">Nombre</label>
                    <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Consulta de tipo de cambio">
                </div>
                <div>
                    <label for="slug" class="mb-1 block text-xs font-medium text-gray-700">Identificador</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug') }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-sm outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="tipo-cambio">
                    <p class="mt-1 text-xs text-gray-500">Va en la dirección y no se puede cambiar después.</p>
                </div>
                <div>
                    <label for="descripcion" class="mb-1 block text-xs font-medium text-gray-700">Qué devuelve</label>
                    <div class="flex gap-2">
                        <input type="text" name="descripcion" id="descripcion" value="{{ old('descripcion') }}"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Compra y venta del día">
                        <button type="submit" class="shrink-0 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                            Crear
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-2.5">Consulta</th>
                        <th class="px-5 py-2.5">Dirección</th>
                        <th class="px-5 py-2.5">Estado</th>
                        <th class="px-5 py-2.5">Este mes</th>
                        <th class="px-5 py-2.5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($apis as $api)
                        <tr class="{{ $api->activa ? '' : 'bg-gray-50/60' }}">
                            <td class="px-5 py-3">
                                <div x-show="editando !== {{ $api->id }}">
                                    <p class="font-medium text-gray-900">{{ $api->nombre }}</p>
                                    <p class="text-xs text-gray-500">{{ $api->descripcion ?: '—' }}</p>
                                </div>

                                <form x-show="editando === {{ $api->id }}" x-cloak
                                      method="POST" action="{{ route('super-admin.consultas.apis.actualizar', $api) }}"
                                      class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="nombre" value="{{ $api->nombre }}" required
                                           class="w-44 rounded-lg border border-gray-300 px-2 py-1.5 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                                    <input type="text" name="descripcion" value="{{ $api->descripcion }}"
                                           class="w-64 rounded-lg border border-gray-300 px-2 py-1.5 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                                    <button type="submit" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                                        Guardar
                                    </button>
                                    <button type="button" @click="editando = null" class="text-xs text-gray-500 hover:underline">
                                        Cancelar
                                    </button>
                                </form>
                            </td>

                            <td class="px-5 py-3">
                                <code class="font-mono text-xs text-gray-600">/api/consultas/{{ $api->slug }}/{numero}</code>
                            </td>

                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $api->activa ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $api->activa ? 'Activa' : 'Apagada' }}
                                    </span>
                                    @if($api->modo_prueba)
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Pruebas</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-5 py-3 font-medium text-gray-900">{{ number_format($api->consultas_mes) }}</td>

                            <td class="px-5 py-3">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <button type="button" @click="editando = editando === {{ $api->id }} ? null : {{ $api->id }}"
                                            class="rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                                        Editar
                                    </button>

                                    <form method="POST" action="{{ route('super-admin.consultas.apis.alternar', $api) }}">
                                        @csrf
                                        <input type="hidden" name="campo" value="modo_prueba">
                                        <button type="submit"
                                                class="rounded-lg border px-2.5 py-1.5 text-xs font-medium transition
                                                       {{ $api->modo_prueba
                                                            ? 'border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100'
                                                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">
                                            {{ $api->modo_prueba ? 'Salir de pruebas' : 'Modo pruebas' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('super-admin.consultas.apis.alternar', $api) }}">
                                        @csrf
                                        <input type="hidden" name="campo" value="activa">
                                        <button type="submit"
                                                class="rounded-lg border px-2.5 py-1.5 text-xs font-medium transition
                                                       {{ $api->activa
                                                            ? 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                                                            : 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100' }}">
                                            {{ $api->activa ? 'Apagar' : 'Encender' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('super-admin.consultas.apis.borrar', $api) }}"
                                          onsubmit="return confirm('Se retira «{{ $api->nombre }}». Quien la esté usando dejará de recibir respuesta. El consumo que hubo se conserva. ¿Continuar?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
                                            Borrar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-gray-500">No ofreces ninguna consulta todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Las llaves se administran en el modulo API, que ya lo hace entero.
         Tenerlas tambien aqui daria dos sitios para desactivar la misma, y uno
         de los dos se quedaria atras. --}}
    <section class="rounded-lg border border-gray-200 bg-white px-5 py-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-gray-900">Las llaves de acceso</p>
                <p class="mt-0.5 text-xs text-gray-500">
                    Se entran con la misma clave de emisión, y se administran en el módulo API.
                </p>
            </div>
            <a href="{{ route('super-admin.api-global') }}"
               class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Ir a API →
            </a>
        </div>
    </section>

</div>
