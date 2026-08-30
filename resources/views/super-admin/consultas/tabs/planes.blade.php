{{-- Una tarjeta por escalon, dentro de cada api. Se ve de un vistazo que
     incluye cada plan de cada servicio, que es la pregunta que se hace uno al
     entrar aqui: "¿cuanto le doy al que paga 29?". --}}
<form method="POST" action="{{ route('super-admin.consultas.cuotas') }}" class="space-y-5">
    @csrf
    @method('PUT')

    @foreach($apis as $api)
        <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg
                                 {{ $api->slug === 'ruc' ? 'bg-blue-50 text-blue-600' : 'bg-purple-50 text-purple-600' }}">
                        @if($api->slug === 'ruc')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        @endif
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $api->nombre }}</p>
                        <p class="text-xs text-gray-500">{{ $api->descripcion }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @unless($api->activa)
                        <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">Apagada</span>
                    @endunless
                    @if($api->modo_prueba)
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">En pruebas</span>
                    @endif
                    @if($api->activa && ! $api->modo_prueba)
                        <span class="rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">Activa</span>
                    @endif
                </div>
            </div>

            <div class="grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($api->planes as $plan)
                    <div class="rounded-lg border p-4 {{ $plan->a_medida ? 'border-amber-200 bg-amber-50/40' : 'border-gray-200' }}">
                        <p class="text-sm font-semibold {{ $plan->a_medida ? 'text-amber-700' : 'text-gray-900' }}">
                            {{ $plan->nombre }}
                        </p>

                        <label for="c_{{ $api->id }}_{{ $plan->id }}" class="mt-3 mb-1 block text-xs text-gray-500">
                            Consultas al mes
                        </label>
                        <input type="number" min="0" max="10000000"
                               id="c_{{ $api->id }}_{{ $plan->id }}"
                               name="cuotas[{{ $api->id }}][{{ $plan->id }}]"
                               value="{{ old('cuotas.' . $api->id . '.' . $plan->id, $plan->pivot->limite_mensual) }}"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">

                        <p class="mt-2 text-xs {{ $plan->a_medida ? 'text-amber-700' : 'text-gray-500' }}">
                            {{ $plan->precio() }}{{ $plan->a_medida ? '' : ' /mes' }}
                        </p>
                        <p class="text-xs text-gray-400">{{ $plan->descripcion }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700">
            Guardar cuotas
        </button>
        <p class="text-xs text-gray-500">
            Un 0 deja ese plan sin acceso a esa consulta: responde 403 en vez de descontar.
            Las consultas no usadas no se acumulan al mes siguiente.
        </p>
    </div>
</form>
