@extends('layouts.consultas')

@section('title', 'Mi API')

@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-semibold text-gray-900">Mi API</h1>
    <p class="text-[15px] text-gray-600">Estas dos cosas van en cada llamada que hace tu programa.</p>
</div>

@if(session('secreto_nuevo'))
    <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4"
         x-data="{ nuevo: @js(session('secreto_nuevo')['secreto']) }">
        <p class="text-sm font-semibold text-amber-900">
            Secreto nuevo de «{{ session('secreto_nuevo')['llave'] }}»
        </p>
        <p class="mb-2 text-xs text-amber-800">El anterior ya no funciona. Cámbialo en tu programa.</p>
        <div class="flex gap-2">
            <code class="flex-1 overflow-x-auto whitespace-nowrap rounded-md border border-amber-300 bg-white px-3 py-2 font-mono text-xs"
                  x-text="nuevo"></code>
            <button type="button"
                    @click="navigator.clipboard.writeText(nuevo); $el.textContent = 'Copiado'"
                    class="rounded-md border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-900">
                Copiar
            </button>
        </div>
    </div>
@endif

@forelse($llaves as $llave)
    <div class="mb-4 rounded-xl border border-gray-200 bg-white"
         x-data="{ visible: false, clave: @js($llave->clave), secreto: @js($llave->secreto) }">

        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-3.5">
            <div class="min-w-0">
                <h2 class="truncate text-base font-semibold text-gray-900">{{ $llave->nombre }}</h2>
                <p class="text-sm text-gray-500">
                    {{ $llave->plan?->nombre ?? 'sin plan' }} ·
                    {{ collect($llave->servicios)->map(fn ($s) => strtoupper($s))->join(' y ') }}
                </p>
            </div>
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                         {{ $llave->entorno === 'produccion' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                {{ $llave->entorno === 'produccion' ? 'Producción' : 'Pruebas' }}
            </span>
        </div>

        <div class="space-y-5 p-5">
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-600">
                    Clave — cabecera <code class="font-mono">X-Api-Key</code>
                </label>
                <div class="flex gap-2">
                    <code class="flex-1 overflow-x-auto whitespace-nowrap rounded-md border border-gray-200 bg-gray-50 px-3.5 py-2.5 font-mono text-sm">{{ $llave->clave }}</code>
                    <button type="button"
                            @click="navigator.clipboard.writeText(clave); $el.textContent = 'Copiada'; setTimeout(() => $el.textContent = 'Copiar', 1400)"
                            class="shrink-0 rounded-md border border-gray-300 px-3.5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Copiar
                    </button>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold text-gray-600">
                    Secreto — cabecera <code class="font-mono">X-Api-Secret</code>,
                    <span class="font-normal text-gray-500">empieza por <code class="font-mono">{{ $llave->secreto_pista }}</code></span>
                </label>
                <div class="flex gap-2">
                    <code class="flex-1 overflow-x-auto whitespace-nowrap rounded-md border border-gray-200 bg-gray-50 px-3.5 py-2.5 font-mono text-sm"
                          x-text="visible ? secreto : '••••••••••••••••••••••••••••••••••••••••'">••••••••••••••••••••••••••••••••••••••••</code>

                    <button type="button" @click="visible = ! visible"
                            class="shrink-0 rounded-md border border-gray-300 px-3.5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        <span x-text="visible ? 'Ocultar' : 'Mostrar'">Mostrar</span>
                    </button>

                    {{-- Copiar sin destapar: si hay alguien mirando la pantalla,
                         no hace falta enseñarlo para llevárselo. --}}
                    <button type="button"
                            @click="navigator.clipboard.writeText(secreto); $el.querySelector('span').textContent = 'Copiado'; setTimeout(() => $el.querySelector('span').textContent = 'Copiar', 1400)"
                            class="shrink-0 rounded-md border border-gray-300 px-3.5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        <span>Copiar</span>
                    </button>
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-4 border-t border-gray-100 pt-4 sm:grid-cols-4">
                <div>
                    <dt class="text-sm text-gray-500">Plan</dt>
                    <dd class="text-[15px] font-semibold text-gray-900">{{ $llave->plan?->nombre ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Servicios</dt>
                    <dd class="text-[15px] font-semibold text-gray-900">{{ collect($llave->servicios)->map(fn ($s) => strtoupper($s))->join(', ') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Entorno</dt>
                    <dd class="text-[15px] font-semibold text-gray-900">{{ $llave->entorno === 'produccion' ? 'Producción' : 'Pruebas' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Vigencia</dt>
                    <dd class="text-[15px] font-semibold tabular-nums text-gray-900">{{ $llave->expira_en?->format('d/m/Y') ?? 'sin caducidad' }}</dd>
                </div>
            </dl>

            <div class="border-t border-gray-100 pt-4">
                <p class="mb-3 text-sm text-gray-600">
                    Si tu secreto se filtró, genera uno nuevo. El anterior deja de valer al instante
                    y <strong class="text-gray-900">tu programa dejará de funcionar</strong> hasta que lo cambies.
                </p>
                <form method="POST" action="{{ route('consultas.secreto.regenerar', $llave->id) }}"
                      x-data="{ enviando: false }" @submit="enviando = true"
                      onsubmit="return confirm('Se generará un secreto nuevo y el actual dejará de funcionar al instante. ¿Seguro?')">
                    @csrf
                    <button type="submit" :disabled="enviando"
                            class="rounded-md border border-red-300 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50 disabled:opacity-60">
                        <span x-text="enviando ? 'Generando…' : 'Generar un secreto nuevo'">Generar un secreto nuevo</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
@empty
    <div class="rounded-xl border border-gray-200 bg-white px-5 py-8 text-center text-sm text-gray-500">
        Todavía no tienes ninguna llave asignada.
    </div>
@endforelse
@endsection
