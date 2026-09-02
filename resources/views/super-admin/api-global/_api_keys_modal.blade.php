{{-- Antes esto era una lista que llevaba a otra ventana, y después un
     desplegable. Las dos cosas pedían un clic para enseñar justo lo que se
     viene a buscar: las credenciales. Ahora se ven de entrada. --}}

<div class="p-5">

    <p class="mb-3 text-sm text-gray-500">
        {{ $apiKeys->count() }} {{ Str::plural('credencial', $apiKeys->count()) }} registrada{{ $apiKeys->count() === 1 ? '' : 's' }}.
    </p>

    <div class="max-h-[65vh] space-y-2 overflow-y-auto">
        @forelse($apiKeys as $apiKey)
            @php
                $esSandbox = (bool) $apiKey->company?->es_demo;

                $credenciales = ['URL base' => url('/api'), 'X-Api-Key' => $apiKey->key];
            @endphp

            <div class="overflow-hidden rounded-lg border border-gray-200">
                <div class="flex items-center gap-3 px-4 py-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $apiKey->active ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M15.75 1.5a6.75 6.75 0 00-6.651 7.906c.067.39-.032.717-.221.906l-6.5 6.499a3 3 0 00-.878 2.121v2.818c0 .414.336.75.75.75H6a.75.75 0 00.75-.75v-1.5h1.5A.75.75 0 009 20.5V19h1.5a.75.75 0 00.53-.22l2.658-2.658c.19-.189.517-.288.906-.22A6.75 6.75 0 1015.75 1.5z"/>
                        </svg>
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-gray-900">{{ $apiKey->company->razon_social ?? 'Sin empresa' }}</span>
                        <span class="block truncate text-xs text-gray-500">{{ $apiKey->name }}</span>
                    </span>

                    <span class="hidden shrink-0 text-right text-xs text-gray-400 sm:block">
                        {{ number_format($consumo[$apiKey->id] ?? 0) }} llamadas
                    </span>

                    <span class="hidden shrink-0 rounded px-2 py-0.5 text-xs font-medium sm:inline {{ $esSandbox ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $esSandbox ? 'Sandbox' : 'Producción' }}
                    </span>

                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $apiKey->active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                        {{ $apiKey->active ? 'Activa' : 'Bloqueada' }}
                    </span>

                </div>

                <div class="border-t border-gray-100 bg-gray-50/70 px-4 py-4">
                    <div class="space-y-2.5">
                        @foreach ($credenciales as $label => $valor)
                            <div class="flex items-center gap-3">
                                <span class="w-24 shrink-0 text-xs font-medium text-gray-500">{{ $label }}</span>
                                <code class="min-w-0 flex-1 break-all rounded-md bg-white px-2.5 py-2 font-mono text-xs text-gray-800 ring-1 ring-gray-200">{{ $valor ?: '—' }}</code>
                                @if ($valor)
                                    <button type="button"
                                            onclick="(function (b, t) {
                                                function ok() {
                                                    b.textContent = 'Copiado';
                                                    b.className = b.dataset.ok;
                                                    setTimeout(function () { b.textContent = 'Copiar'; b.className = b.dataset.normal; }, 1500);
                                                }
                                                function alternativa() {
                                                    var c = document.createElement('textarea');
                                                    c.value = t; c.style.position = 'fixed'; c.style.opacity = '0';
                                                    document.body.appendChild(c); c.select();
                                                    try { document.execCommand('copy'); ok(); }
                                                    catch (e) { b.textContent = 'Copia a mano'; }
                                                    document.body.removeChild(c);
                                                }
                                                var moderno = navigator.clipboard ? window.isSecureContext : false;
                                                if (moderno) { navigator.clipboard.writeText(t).then(ok).catch(alternativa); }
                                                else { alternativa(); }
                                            })(this, @js($valor))"
                                            data-normal="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
                                            data-ok="shrink-0 rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm"
                                            class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                                @endif
                            </div>
                        @endforeach

                        {{-- El secret, debajo de la key y tapado hasta que se pide.

                             No viaja con la pagina: en un listado de empresas saldria
                             el de todas en cada carga. Se trae al abrir esta ficha,
                             asi que al pulsar «Mostrar» ya esta. --}}
                        <div class="flex items-center gap-2"
                             x-data="{ visible: false, valor: null, cargando: false,
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
                             }"
                             x-init="traer()">
                            <span class="w-24 shrink-0 text-xs font-medium text-gray-600">X-Api-Secret</span>

                            <code class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap rounded border border-gray-200 bg-white px-2 py-1.5 font-mono text-xs"
                                  :class="visible ? 'text-gray-800' : 'text-gray-400'">
                                <span x-show="! visible">··················</span>
                                <span x-show="visible" x-text="valor"></span>
                            </code>

                            <button type="button" @click="mostrar()" :disabled="cargando"
                                    class="shrink-0 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                                    x-text="cargando ? '…' : (visible ? 'Ocultar' : 'Mostrar')"></button>

                            <button type="button" x-show="visible" x-cloak
                                    @click="window.copyCompanyCredential($el, valor)"
                                    class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                        </div>

                        <p class="text-xs text-gray-500">Si el cliente lo perdió, genérale uno nuevo con el botón de renovar de su fila —su X-Api-Key no cambia—.</p>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 pt-3">
                        <span class="text-xs text-gray-500">
                            Último uso: {{ $apiKey->last_used_at?->diffForHumans() ?? 'nunca' }}
                        </span>

                        <div class="flex gap-2">
                            <button type="button"
                                    onclick="window.openAdminModal('{{ route('super-admin.api-global.key-actividad', $apiKey) }}', 'Actividad de la credencial')"
                                    class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Ver actividad</button>

                            <form method="POST" action="{{ route('super-admin.api-global.toggle-key', $apiKey) }}"
                                  data-success-message="Credencial {{ $apiKey->active ? 'bloqueada' : 'activada' }} correctamente."
                                  onsubmit="return confirm('{{ $apiKey->active ? 'Se rechazarán todas las peticiones con esta credencial. ¿Bloquearla?' : '¿Activar esta credencial?' }}')">
                                @csrf
                                <button type="submit" class="rounded-md px-3 py-1.5 text-xs font-medium text-white {{ $apiKey->active ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                                    {{ $apiKey->active ? 'Bloquear' : 'Activar' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p class="rounded-lg border border-gray-200 px-4 py-10 text-center text-sm text-gray-500">
                Ninguna empresa tiene credenciales de API todavía.
            </p>
        @endforelse
    </div>

</div>
