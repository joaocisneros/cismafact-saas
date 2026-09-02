@extends('layouts.app')

@section('title', 'API Keys')

@section('content')
<div class="space-y-5"
     x-data="{
        copiado: null,
        copiar(valor, id) {
            /* navigator.clipboard solo existe en HTTPS o en localhost, y el
               panel corre en un dominio .test: ahi el boton no hacia nada.
               Se intenta, y si no esta se copia con un textarea temporal. */
            const hecho = () => {
                this.copiado = id;
                setTimeout(() => this.copiado = null, 1500);
            };

            const aMano = () => {
                const caja = document.createElement('textarea');
                caja.value = valor;
                caja.style.position = 'fixed';
                caja.style.opacity = '0';
                document.body.appendChild(caja);
                caja.select();
                try { document.execCommand('copy'); hecho(); } catch (e) {}
                document.body.removeChild(caja);
            };

            if (navigator.clipboard ? window.isSecureContext : false) {
                navigator.clipboard.writeText(valor).then(hecho).catch(aMano);
            } else {
                aMano();
            }
        },
     }">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">API Keys</h1>
            <p class="mt-1 text-sm text-gray-500">Para conectar otro sistema con tu facturación.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('empresa.api-keys.documentation') }}"
               class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                Documentación
            </a>
            <button type="button"
                    onclick="window.openAdminModal('{{ route('empresa.api-keys.create') }}?modal=1', 'Nueva API Key')"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                + Nueva API Key
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Una tarjeta por credencial. En tabla, las dos claves largas apretaban
         las demás columnas y todo quedaba amontonado. --}}
    <div class="space-y-3">
        @forelse($apiKeys as $apiKey)
            <div class="rounded-xl border border-gray-200 bg-white p-5">

                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-gray-900">{{ $apiKey->name }}</h2>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $apiKey->active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $apiKey->active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Creada el {{ $apiKey->created_at->format('d/m/Y') }} ·
                            Último uso: {{ optional($apiKey->last_used_at)->diffForHumans() ?? 'nunca' }}
                        </p>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <form method="POST" action="{{ route('empresa.api-keys.toggle', $apiKey) }}">
                            @csrf
                            <x-icon-action :icon="$apiKey->active ? 'suspender' : 'activar'"
                                           :label="$apiKey->active ? 'Desactivar credencial' : 'Activar credencial'"
                                           :color="$apiKey->active ? 'amber' : 'emerald'" />
                        </form>
                        <form method="POST" action="{{ route('empresa.api-keys.regenerate', $apiKey) }}"
                              onsubmit="return confirm('¿Regenerar el secret de «{{ $apiKey->name }}»? El sistema que la use dejará de funcionar hasta que cambies el valor.')">
                            @csrf
                            <x-icon-action icon="renovar" label="Regenerar secret" color="orange" />
                        </form>
                        <form method="POST" action="{{ route('empresa.api-keys.destroy', $apiKey) }}"
                              onsubmit="return confirm('¿Eliminar «{{ $apiKey->name }}»? Es permanente y quien la use dejará de poder emitir.')">
                            @csrf
                            @method('DELETE')
                            <x-icon-action icon="eliminar" label="Eliminar credencial" color="red" />
                        </form>
                    </div>
                </div>

                <div class="mt-4 space-y-2">
                    <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                        <span class="w-24 shrink-0 font-mono text-xs text-gray-500">X-Api-Key</span>
                        <code class="min-w-0 flex-1 truncate font-mono text-xs text-gray-800">{{ $apiKey->key }}</code>
                        <button type="button" @click="copiar(@js($apiKey->key), 'k{{ $apiKey->id }}')"
                                class="shrink-0 rounded px-2 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-50">
                            <span x-text="copiado === 'k{{ $apiKey->id }}' ? 'Copiado' : 'Copiar'"></span>
                        </button>
                    </div>

                    {{-- Tapado hasta que se pide: no sale con la pagina, que con
                         varias credenciales irian todos los secretos en cada
                         carga. Se trae al abrir la pantalla, asi que al pulsar
                         «Mostrar» ya esta puesto. --}}
                    <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2"
                         x-data="{ visible: false, valor: null, cargando: false,
                             async traer() {
                                 this.cargando = true;
                                 try {
                                     const r = await fetch('{{ route('empresa.api-keys.show-secret', $apiKey) }}', {
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
                        <span class="w-24 shrink-0 font-mono text-xs text-gray-500">X-Api-Secret</span>

                        <code class="min-w-0 flex-1 truncate font-mono text-xs" :class="visible ? 'text-gray-800' : 'text-gray-400'">
                            <span x-show="! visible">••••••••••••••••</span>
                            <span x-show="visible" x-text="valor"></span>
                        </code>

                        <button type="button" @click="mostrar()" :disabled="cargando"
                                class="shrink-0 rounded px-2 py-1 text-xs font-semibold text-gray-600 hover:bg-gray-200 disabled:opacity-50"
                                x-text="cargando ? '…' : (visible ? 'Ocultar' : 'Mostrar')"></button>

                        <button type="button" x-show="visible" x-cloak @click="copiar(valor, 's{{ $apiKey->id }}')"
                                class="shrink-0 rounded px-2 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-50">
                            <span x-text="copiado === 's{{ $apiKey->id }}' ? 'Copiado' : 'Copiar'"></span>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-12 text-center">
                <p class="text-gray-600">Todavía no tienes credenciales.</p>
                <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500">
                    Solo hacen falta para conectar otro sistema. Para facturar desde aquí no necesitas ninguna.
                </p>
            </div>
        @endforelse
    </div>
</div>
@endsection
