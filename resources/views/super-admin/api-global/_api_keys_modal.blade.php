{{-- Las credenciales de una empresa.

     Usa la misma ficha que las otras tres pantallas donde salen credenciales:
     antes cada una tenía su ancho, sus colores y su forma de colocar lo mismo.

     Aquí puede haber varias en la misma ventana, así que van una debajo de
     otra sin la cabecera de la ficha —el nombre ya lo pone cada bloque—. --}}

<div class="px-6 py-5">

    <p class="mb-3 text-sm text-gray-500">
        {{ $apiKeys->count() }} {{ Str::plural('credencial', $apiKeys->count()) }} registrada{{ $apiKeys->count() === 1 ? '' : 's' }}.
    </p>

    <div class="max-h-[65vh] space-y-4 overflow-y-auto">
        @forelse($apiKeys as $apiKey)
            @php $esSandbox = (bool) $apiKey->company?->es_demo; @endphp

            <x-ficha-credencial :suelta="false">
                <x-slot:titulo>{{ $apiKey->company->razon_social ?? 'Sin empresa' }}</x-slot:titulo>
                <x-slot:subtitulo>{{ $apiKey->name }}</x-slot:subtitulo>

                @php
                    // Una credencial activa de una empresa inactiva no sirve: la
                    // API responde 403 y la ficha decia «Activa» sin mas, asi que
                    // el aviso del cliente no cuadraba con lo que se veia aqui.
                    $empresaParada = ! ($apiKey->company?->activo ?? true);
                @endphp

                <x-slot:estado>
                    @if($empresaParada)
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700"
                              title="La credencial está activa, pero su empresa no: la API responde 403">
                            Empresa inactiva
                        </span>
                    @else
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $apiKey->active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                            {{ $apiKey->active ? '● Activa' : 'Bloqueada' }}
                        </span>
                    @endif
                </x-slot:estado>

                <x-slot:etiqueta>
                    <span class="rounded px-2 py-0.5 text-xs font-medium {{ $esSandbox ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                        {{ $esSandbox ? 'SUNAT beta' : 'Producción' }}
                    </span>
                </x-slot:etiqueta>

                <x-slot:credenciales>
                    <x-fila-credencial etiqueta="URL base" descripcion="A dónde van todas las peticiones" tono="indigo">
                        <x-slot:icono><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3.6 9h16.8M3.6 15h16.8M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/></svg></x-slot:icono>
                        {{ url('/api') }}
                        <x-slot:boton>
                            <button type="button" onclick="window.copyCompanyCredential(this, @js(url('/api')))"
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>Copiar</button>
                        </x-slot:boton>
                    </x-fila-credencial>

                    <x-fila-credencial etiqueta="X-Api-Key" descripcion="Identifica quién llama. No es secreta" tono="violet">
                        <x-slot:icono><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4-2a6 6 0 01-7.743 5.743L11 15H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 7z"/></svg></x-slot:icono>
                        {{ $apiKey->key }}
                        <x-slot:boton>
                            <button type="button" onclick="window.copyCompanyCredential(this, @js($apiKey->key))"
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>Copiar</button>
                        </x-slot:boton>
                    </x-fila-credencial>

                    {{-- El secret se pide al abrir la ficha, no con la página: aquí
                         pueden salir varias credenciales y viajarían todos los
                         secretos en cada carga. --}}
                    <div x-data="{ visible: false, valor: null, cargando: false,
                             async traer() {
                                 this.cargando = true;
                                 try {
                                     const r = await fetch('{{ route('super-admin.api-global.secret-key', $apiKey) }}', {
                                         headers: { 'Accept': 'application/json' },
                                     });
                                     this.valor = (await r.json()).secret;
                                 } catch (e) {
                                     this.valor = null;
                                 } finally {
                                     this.cargando = false;
                                 }
                             },
                             async mostrar() {
                                 if (this.visible) { this.visible = false; return; }
                                 if (! this.valor) await this.traer();
                                 this.visible = !! this.valor;
                             },
                         }" x-init="traer()">
                        <x-fila-credencial etiqueta="X-Api-Secret" descripcion="La que firma. No debe salir de su sistema" tono="amber">
                            <x-slot:icono><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></x-slot:icono>
                            <span x-show="! visible" class="text-gray-400">··················</span>
                            <span x-show="visible" x-text="valor"></span>
                            <x-slot:boton>
                                <button type="button" @click="mostrar()" :disabled="cargando"
                                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><span x-text="cargando ? '…' : (visible ? 'Ocultar' : 'Mostrar')"></span></button>

                                <button type="button" x-show="visible" x-cloak
                                        @click="window.copyCompanyCredential($el, valor)"
                                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>Copiar</button>
                            </x-slot:boton>
                        </x-fila-credencial>
                    </div>
                </x-slot:credenciales>


                <x-slot:metricas>
                    <x-metrica-credencial titulo="Llamadas" tono="violet">
                        <x-slot:icono><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11 11 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg></x-slot:icono>{{ number_format($consumo[$apiKey->id] ?? 0) }}</x-metrica-credencial>
                    <x-metrica-credencial titulo="Último uso" tono="blue">
                        <x-slot:icono><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icono>{{ $apiKey->last_used_at?->diffForHumans(short: true) ?? 'Nunca' }}</x-metrica-credencial>
                    <x-metrica-credencial titulo="Creada" tono="green">
                        <x-slot:icono><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></x-slot:icono>{{ $apiKey->created_at->format('d/m/Y') }}</x-metrica-credencial>
                    <x-metrica-credencial titulo="Vence" tono="amber" :destacado="true">
                        <x-slot:icono><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></x-slot:icono>{{ $apiKey->expires_at?->format('d/m/Y') ?? 'Nunca' }}</x-metrica-credencial>
                </x-slot:metricas>

                <x-slot:acciones>
                    <button type="button"
                            onclick="window.openAdminModal('{{ route('super-admin.api-global.key-actividad', $apiKey) }}', 'Actividad de la credencial')"
                            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Ver actividad</button>

                    <form method="POST" action="{{ route('super-admin.api-global.toggle-key', $apiKey) }}"
                          data-success-message="Credencial {{ $apiKey->active ? 'bloqueada' : 'activada' }} correctamente."
                          onsubmit="return confirm('{{ $apiKey->active ? 'Se rechazarán todas las peticiones con esta credencial. ¿Bloquearla?' : '¿Activar esta credencial?' }}')">
                        @csrf
                        <button type="submit"
                                class="rounded-md px-3 py-1.5 text-xs font-medium text-white {{ $apiKey->active ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                            {{ $apiKey->active ? 'Bloquear' : 'Activar' }}
                        </button>
                    </form>
                </x-slot:acciones>
            </x-ficha-credencial>
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 px-4 py-10 text-center">
                <p class="text-sm text-gray-500">Ninguna empresa tiene credenciales de API todavía.</p>
            </div>
        @endforelse
    </div>
</div>
