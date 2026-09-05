@extends('layouts.consultas')

@section('title', 'Mi API')

@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-semibold text-gray-900">Mi API</h1>
    <p class="text-[15px] text-gray-600">Lo que va en cada llamada que hace tu programa.</p>
</div>

@forelse($llaves as $llave)
    <div class="mb-4 rounded-xl border border-gray-200 bg-white"
         x-data="{
             visible: @js(session('secreto_nuevo') === $llave->id),
             reciente: @js(session('secreto_nuevo') === $llave->id),
             clave: @js($llave->clave),
             secreto: @js($llave->secreto),
         }">

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-3.5">
            <div class="min-w-0">
                <h2 class="truncate text-base font-semibold text-gray-900">{{ $llave->nombre }}</h2>
                <p class="text-sm text-gray-500">
                    {{ $llave->plan?->nombre ?? 'sin plan' }} ·
                    {{ collect($llave->servicios)->map(fn ($s) => strtoupper($s))->join(' y ') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                             {{ $llave->entorno === 'produccion' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $llave->entorno === 'produccion' ? 'Producción' : 'Pruebas' }}
                </span>

                {{-- A la vista y no detrás de un menú: cuando un secreto se filtra
                     hay prisa. Lleva confirmación porque deja sin funcionar la
                     integración de quien lo pulsa. --}}
                <form method="POST" action="{{ route('consultas.secreto.regenerar', $llave->id) }}"
                      x-data="{ enviando: false }" @submit="enviando = true"
                      onsubmit="return confirm('Se generará un secreto nuevo y el actual dejará de funcionar al instante. Tu programa dejará de responder hasta que lo cambies. ¿Seguro?')">
                    @csrf
                    <button type="submit" :disabled="enviando"
                            title="Solo si el tuyo se filtró: el actual deja de valer"
                            class="flex items-center gap-1.5 rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:border-red-300 hover:bg-red-50 hover:text-red-700 disabled:opacity-60">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-3-6.7M21 3v6h-6"/>
                        </svg>
                        <span x-text="enviando ? 'Generando…' : 'Regenerar secreto'">Regenerar secreto</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="space-y-5 p-5">
            {{-- El título de cada campo es lo que se escribe en el código —la
                 dirección, el nombre de la cabecera— y al lado lo que significa.
                 Al revés, «Clave — cabecera X-Api-Key» obligaba a leer la frase
                 entera para dar con el dato. --}}
            <div>
                <div class="mb-1.5 flex flex-wrap items-baseline gap-x-2">
                    <span class="font-mono text-sm font-semibold text-gray-900">URL base</span>
                    <span class="text-sm text-gray-500">todas las llamadas empiezan por aquí</span>
                </div>
                <div class="flex gap-2">
                    <code class="flex-1 overflow-x-auto whitespace-nowrap rounded-md border border-gray-200 bg-gray-50 px-3.5 py-2.5 font-mono text-sm">{{ url('/api/consultas') }}</code>
                    <button type="button"
                            @click="navigator.clipboard.writeText(@js(url('/api/consultas'))); $el.textContent = 'Copiada'; setTimeout(() => $el.textContent = 'Copiar', 1400)"
                            class="shrink-0 rounded-md border border-gray-300 px-3.5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Copiar
                    </button>
                </div>
            </div>

            <div>
                <div class="mb-1.5 flex flex-wrap items-baseline gap-x-2">
                    <span class="font-mono text-sm font-semibold text-gray-900">X-Api-Key</span>
                    <span class="text-sm text-gray-500">identifica quién llama · no es secreta</span>
                </div>
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
                <div class="mb-1.5 flex flex-wrap items-baseline gap-x-2">
                    <span class="font-mono text-sm font-semibold text-gray-900">X-Api-Secret</span>
                    <span class="text-sm text-gray-500">
                        la que firma · no la compartas · el tuyo empieza por
                        <code class="font-mono text-gray-700">{{ $llave->secreto_pista }}</code>
                    </span>

                    {{-- El recien generado se enseña aqui mismo y destapado, no
                         en un aviso arriba que lo repetia: se copia del sitio
                         donde ya se estaba mirando. --}}
                    <span x-show="reciente" x-cloak
                          class="rounded bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                        recién generado · cópialo, el anterior ya no vale
                    </span>
                </div>
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

            <dl class="grid grid-cols-2 gap-4 border-t border-gray-100 pt-5 sm:grid-cols-4">
                <div>
                    <dt class="text-sm text-gray-500">Plan</dt>
                    <dd class="text-[15px] font-semibold text-gray-900">{{ $llave->plan?->nombre ?? '—' }}</dd>
                    {{-- El importe de esta llave en concreto: se cobra por los
                         servicios que tiene, no por el plan entero. --}}
                    @if($llave->plan && ! $llave->plan->esGratis())
                        <dd class="text-sm text-gray-500">{{ $llave->precioTexto() }} al mes</dd>
                    @endif
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
        </div>
    </div>
@empty
    <div class="rounded-xl border border-gray-200 bg-white px-5 py-10 text-center text-[15px] text-gray-500">
        Todavía no tienes ninguna llave asignada.
    </div>
@endforelse
@endsection
