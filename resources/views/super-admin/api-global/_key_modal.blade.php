{{-- La ficha de un token de prueba.

     Es la misma que en las otras tres pantallas donde salen credenciales: antes
     cada una colocaba lo mismo a su manera —otro ancho, otros colores, la clave
     repetida arriba y abajo— y se parecían lo justo para despistar. --}}

@php
    $esSandbox = (bool) $apiKey->company?->es_demo;
@endphp

<div class="p-5">
    <x-ficha-credencial :suelta="false">
        <x-slot:titulo>{{ $apiKey->name }}</x-slot:titulo>
        <x-slot:subtitulo>{{ $apiKey->company->razon_social ?? 'Sin empresa' }}</x-slot:subtitulo>

        @php
            // La empresa manda por encima de la credencial: si esta parada, la
            // API responde 403 aunque la credencial figure como activa.
            $empresaParada = ! ($apiKey->company?->activo ?? true);
        @endphp

        <x-slot:estado>
            @if($empresaParada)
                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700"
                      title="La credencial está activa, pero su empresa no: la API responde 403">
                    Empresa inactiva
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $apiKey->active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $apiKey->active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                    {{ $apiKey->active ? 'Activa' : 'Bloqueada' }}
                </span>
            @endif
        </x-slot:estado>

        <x-slot:etiqueta>
            <span class="rounded px-2 py-0.5 text-xs font-medium {{ $esSandbox ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                {{ $esSandbox ? 'SUNAT beta' : 'Empresa real' }}
            </span>
        </x-slot:etiqueta>

        <x-slot:credenciales>
            <x-fila-credencial etiqueta="URL base">
                {{ url('/api') }}
                <x-slot:boton>
                    <button type="button" onclick="window.copyCompanyCredential(this, @js(url('/api')))"
                            class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                </x-slot:boton>
            </x-fila-credencial>

            <x-fila-credencial etiqueta="X-Api-Key">
                {{ $apiKey->key }}
                <x-slot:boton>
                    <button type="button" onclick="window.copyCompanyCredential(this, @js($apiKey->key))"
                            class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                </x-slot:boton>
            </x-fila-credencial>

            {{-- Se pide al abrir la ficha, no con la página: en un listado
                 saldría el secret de todas las credenciales en cada carga. --}}
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
                <x-fila-credencial etiqueta="X-Api-Secret">
                    <span x-show="! visible" class="text-gray-400">··················</span>
                    <span x-show="visible" x-text="valor"></span>
                    <x-slot:boton>
                        <button type="button" @click="mostrar()" :disabled="cargando"
                                class="shrink-0 rounded-md border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50"
                                x-text="cargando ? '…' : (visible ? 'Ocultar' : 'Mostrar')"></button>

                        <button type="button" x-show="visible" x-cloak
                                @click="window.copyCompanyCredential($el, valor)"
                                class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Copiar</button>
                    </x-slot:boton>
                </x-fila-credencial>
            </div>
        </x-slot:credenciales>

        <x-slot:nota>
            No sale con la página: se pide solo al abrir esta ficha.
        </x-slot:nota>

        <x-slot:metricas>
            <x-metrica-credencial titulo="Llamadas">{{ number_format($totalUsage) }}</x-metrica-credencial>
            <x-metrica-credencial titulo="Último uso">{{ $apiKey->last_used_at?->diffForHumans(short: true) ?? 'Nunca' }}</x-metrica-credencial>
            <x-metrica-credencial titulo="Creada">{{ $apiKey->created_at->format('d/m/Y') }}</x-metrica-credencial>
            <x-metrica-credencial titulo="Caduca">{{ $apiKey->expires_at?->format('d/m/Y') ?? 'Nunca' }}</x-metrica-credencial>
        </x-slot:metricas>

        <x-slot:acciones>
            <form method="POST"
                  action="{{ route('super-admin.api-global.toggle-key', $apiKey) }}"
                  data-success-message="API Key {{ $apiKey->active ? 'bloqueada' : 'activada' }} correctamente."
                  onsubmit="return confirm('{{ $apiKey->active ? 'Se rechazarán todas las peticiones con esta credencial. ¿Bloquearla?' : '¿Activar esta credencial?' }}')">
                @csrf
                <button type="submit"
                        class="rounded-md px-4 py-2 text-sm font-medium text-white {{ $apiKey->active ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                    {{ $apiKey->active ? 'Bloquear' : 'Activar' }}
                </button>
            </form>
        </x-slot:acciones>
    </x-ficha-credencial>
</div>
