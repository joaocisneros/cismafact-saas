{{-- Con el aspecto de una tabla de precios, pero editable: esta pantalla es
     donde se decide cuanto incluye cada plan, no donde se contrata. El numero
     va grande porque es lo que se viene a mirar y a cambiar. --}}
<form method="POST" action="{{ route('super-admin.consultas.cuotas') }}" class="space-y-5">
    @csrf
    @method('PUT')

    @php
        // Un color por escalon, el mismo en las dos apis: asi se compara de un
        // vistazo "el Pro de RUC" con "el Pro de DNI".
        $tonos = [
            'gratis' => ['texto' => 'text-green-600', 'borde' => 'border-gray-200', 'fondo' => 'bg-white'],
            'basico' => ['texto' => 'text-blue-600', 'borde' => 'border-gray-200', 'fondo' => 'bg-white'],
            'pro' => ['texto' => 'text-purple-600', 'borde' => 'border-purple-300', 'fondo' => 'bg-purple-50/30'],
            'empresarial' => ['texto' => 'text-orange-600', 'borde' => 'border-orange-200', 'fondo' => 'bg-orange-50/30'],
        ];
    @endphp

    @foreach($apis as $api)
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl
                                 {{ $api->slug === 'ruc' ? 'bg-green-50 text-green-600' : 'bg-purple-50 text-purple-600' }}">
                        @if($api->slug === 'ruc')
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        @else
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        @endif
                    </span>
                    <div>
                        <p class="text-base font-bold text-gray-900">{{ strtoupper($api->slug) === 'RUC' ? 'API RUC' : 'API DNI' }}</p>
                        <p class="text-xs text-gray-500">{{ $api->descripcion }}</p>
                    </div>
                </div>

                <span class="rounded-full px-2.5 py-1 text-xs font-medium
                    @if(! $api->activa) bg-red-50 text-red-700
                    @elseif($api->modo_prueba) bg-amber-50 text-amber-700
                    @else bg-green-50 text-green-700 @endif">
                    {{ ! $api->activa ? 'Apagada' : ($api->modo_prueba ? 'En pruebas' : 'Activa') }}
                </span>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($api->planes as $plan)
                    @php $t = $tonos[$plan->slug] ?? $tonos['gratis']; @endphp

                    <div class="rounded-xl border-2 p-4 text-center transition {{ $t['borde'] }} {{ $t['fondo'] }}">
                        <p class="text-sm font-bold {{ $t['texto'] }}">{{ $plan->nombre }}</p>

                        <input type="number" min="0" max="10000000"
                               id="c_{{ $api->id }}_{{ $plan->id }}"
                               name="cuotas[{{ $api->id }}][{{ $plan->id }}]"
                               value="{{ old('cuotas.' . $api->id . '.' . $plan->id, $plan->pivot->limite_mensual) }}"
                               class="mt-3 w-full rounded-lg border border-transparent bg-transparent text-center text-2xl font-bold text-gray-900
                                      transition hover:border-gray-200 focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <label for="c_{{ $api->id }}_{{ $plan->id }}" class="block text-xs text-gray-500">consultas/mes</label>

                        <p class="mt-3 border-t border-gray-100 pt-3 text-base font-bold text-gray-900">
                            {{ $plan->a_medida ? 'Personalizado' : 'S/ ' . rtrim(rtrim(number_format((float) $plan->precio_mensual, 2), '0'), '.') }}
                            @unless($plan->a_medida)
                                <span class="text-xs font-normal text-gray-500">/mes</span>
                            @endunless
                        </p>
                        <p class="mt-0.5 text-xs text-gray-400">{{ $plan->descripcion }}</p>
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
            Las no usadas no se acumulan al mes siguiente.
        </p>
    </div>
</form>
