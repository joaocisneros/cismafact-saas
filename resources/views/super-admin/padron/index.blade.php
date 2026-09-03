@extends('layouts.app')

@section('title', 'Padrón SUNAT')

@section('content')
<div class="space-y-5"
     x-data="{
         enMarcha: {{ $enMarcha ? 'true' : 'false' }},
         filas: {{ $filas }},
         importadas: 0,
         estado: null,
         init() { if (this.enMarcha) this.vigilar(); },
         vigilar() {
             setInterval(async () => {
                 try {
                     const r = await fetch('{{ route('super-admin.padron.estado') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                     const d = await r.json();
                     this.filas = d.filas;
                     this.importadas = d.importadas;
                     this.estado = d.estado;
                     if (this.enMarcha && !d.en_marcha) location.reload();
                     this.enMarcha = d.en_marcha;
                 } catch (e) { /* si falla una vuelta, se reintenta en la siguiente */ }
             }, 5000);
         },
     }">

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Las mismas tarjetas del Dashboard: es el mismo panel, y aqui tenian su
         propio aspecto para decir lo mismo. --}}
    <section class="grid gap-4 sm:grid-cols-3">
        <x-stat-card title="RUC en el padrón local"
                     :value="number_format($filas)"
                     :subtitle="$filas ? 'Responde sin salir a internet' : 'Sin importar todavía'"
                     :color="$filas ? 'blue' : 'slate'">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        {{-- En rojo cuando no cabe: es lo unico de aqui que impide importar. --}}
        <x-stat-card title="Espacio libre en disco"
                     :value="$espacio['libre'] !== null ? $espacio['libre'] . ' GB' : '—'"
                     :subtitle="'Hacen falta unos ' . $espacio['necesario'] . ' GB'"
                     :color="($espacio['libre'] ?? 0) >= $espacio['necesario'] ? 'green' : 'red'">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.858 5.858A9 9 0 1118.142 18.142 9 9 0 015.858 5.858zM12 8v4l3 3"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        {{-- Se pinta con Alpine porque cambia sola mientras corre la importacion. --}}
        <div x-show="!enMarcha">
            <x-stat-card title="Estado"
                         :value="$filas ? 'Al día' : 'Vacío'"
                         :subtitle="$filas ? 'El padrón está cargado' : 'Todavía no se ha importado'"
                         :color="$filas ? 'green' : 'slate'">
                <x-slot:icon>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </x-slot:icon>
            </x-stat-card>
        </div>

        <div x-show="enMarcha" x-cloak>
            <x-stat-card title="Estado" value="En marcha" color="indigo">
                <x-slot:subtitle>
                    <span x-text="importadas.toLocaleString('es-PE')"></span> importados
                </x-slot:subtitle>
                <x-slot:icon>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </x-slot:icon>
            </x-stat-card>
        </div>
    </section>

    {{-- Se lee una vez y estorba siempre: cerrado ocupa una linea, y sigue
         estando para quien lo necesite. --}}
    <details class="rounded-lg border border-gray-200 bg-white px-5 py-3 text-sm">
        <summary class="cursor-pointer font-medium text-gray-700 hover:text-gray-900">
            Qué es el padrón y qué no cubre
        </summary>
        <div class="mt-3 space-y-2 border-t border-gray-100 pt-3 text-gray-600">
            <p>
                Una copia del <strong>padrón reducido</strong> que SUNAT publica. Con ella, consultar un RUC
                no sale a internet: se responde desde la propia base, sin límite y en milisegundos.
            </p>
            <p>
                <strong class="text-gray-800">Trae lo mismo que el servidor de consultas</strong> —nombre, estado,
                condición y domicilio— y nada más. La fecha de inscripción, la actividad económica y el tipo de
                contribuyente solo están en la ficha web de SUNAT, detrás de un captcha. Esto da independencia,
                no información nueva.
            </p>
            <p>
                <strong class="text-gray-800">Y envejece.</strong> Es una foto del día en que SUNAT publicó el
                archivo: un RUC inscrito después no figura, y uno dado de baja ayer sigue apareciendo como
                activo. Por eso el servidor de consultas no sobra: el padrón responde primero y él cubre lo que falte.
            </p>
        </div>
    </details>

    {{-- Preguntar y ver la respuesta, uno al lado del otro: la respuesta
         salia debajo del formulario y quedaba fuera de pantalla justo al
         pulsar, que es cuando se quiere leer. --}}
    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900">Servidor de consultas</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                @if($filas)
                    Cubre lo que el padrón no tenga: los RUC inscritos después de la última descarga.
                @else
                    Mientras el padrón esté vacío, todo sale de aquí.
                @endif
            </p>
        </div>

        <div class="grid lg:h-64 lg:grid-cols-2 lg:divide-x lg:divide-gray-100">

            {{-- Izquierda: a quién se pregunta y con qué. --}}
            <div class="space-y-4 overflow-y-auto p-5">
                {{-- Tapada por defecto: esta ahi por si hace falta comprobarla,
                     pero se mira una vez y luego solo estorba en una pantalla
                     que se enseña a otros. --}}
                <div class="flex flex-wrap items-center gap-2" x-data="{ verUrl: false }">
                    <span class="text-xs font-medium text-gray-500">Se pregunta a</span>

                    <code x-show="verUrl" x-cloak
                          class="min-w-0 flex-1 truncate rounded bg-gray-50 px-2 py-1 font-mono text-xs text-gray-700">{{ ($ajustes['consultas_url'] ?? '') ?: config('consultas.url') }}</code>
                    <code x-show="!verUrl"
                          class="min-w-0 flex-1 select-none truncate rounded bg-gray-50 px-2 py-1 font-mono text-xs tracking-widest text-gray-400">••••••••••••••••••••</code>

                    <button type="button" @click="verUrl = !verUrl"
                            class="shrink-0 rounded-md border border-gray-300 bg-white p-1.5 text-gray-500 hover:bg-gray-50"
                            x-bind:title="verUrl ? 'Ocultar la dirección' : 'Ver la dirección'"
                            x-bind:aria-label="verUrl ? 'Ocultar la dirección' : 'Ver la dirección'">
                        <span x-show="!verUrl"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></span>
                        <span x-show="verUrl" x-cloak><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.6 10.6a3 3 0 004.2 4.2M9.9 5.2A9.6 9.6 0 0112 5c4.5 0 8.3 2.9 9.5 7a10.6 10.6 0 01-4 5.2M6.6 6.6A10.6 10.6 0 002.5 12c1.3 4.1 5 7 9.5 7 1 0 1.9-.1 2.8-.4"/></svg></span>
                    </button>
                </div>

                <form method="POST" action="{{ route('super-admin.padron.probar') }}"
                      class="flex flex-wrap items-center gap-2"
                      x-data="{ tipo: '{{ old('tipo', 'ruc') }}' }">
                    @csrf
                    <select name="tipo" x-model="tipo"
                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="ruc">RUC</option>
                        <option value="dni">DNI</option>
                    </select>

                    {{-- Cada documento tiene su medida: el campo no deja escribir
                         de mas y el ejemplo dice cuantas cifras son. Antes ponia
                         un RUC de verdad, el de una empresa real. --}}
                    <input type="text" name="numero" value="{{ old('numero') }}"
                           inputmode="numeric"
                           x-bind:maxlength="tipo === 'dni' ? 8 : 11"
                           x-bind:placeholder="tipo === 'dni' ? '8 dígitos' : '11 dígitos'"
                           x-on:input="$el.value = $el.value.replace(/\D/g, '')"
                           class="w-40 rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="11 dígitos">
                    <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                        Probar
                    </button>
                </form>

                <p class="text-xs text-gray-500">Pregunta de verdad, sin usar lo guardado.</p>
            </div>

            {{-- Derecha: lo que contesta. --}}
            <div class="min-h-0 p-5">
                @if($r = session('consulta_prueba'))
                    @php
                        /*
                         * «valido» dice que el numero esta bien escrito, no que
                         * exista: un DNI inventado de ocho cifras es «valido».
                         * Lo que importa aqui es si se trajo la ficha, y eso lo
                         * dice la fuente. Antes salia «55555555 — válido» en
                         * verde junto a «Ese DNI no figura en RENIEC».
                         */
                        $seEncontro = ($r['fuente'] ?? 'ninguna') !== 'ninguna';
                        $malEscrito = ! ($r['valido'] ?? false);
                    @endphp
                    <div class="h-full overflow-y-auto rounded-lg border px-4 py-3 text-sm {{ $seEncontro ? 'border-green-200 bg-green-50/30' : ($malEscrito ? 'border-red-200 bg-red-50/30' : 'border-amber-200 bg-amber-50/30') }}">
                        <p class="font-semibold {{ $seEncontro ? 'text-green-800' : ($malEscrito ? 'text-red-800' : 'text-amber-800') }}">
                            {{ $r['numero'] }} —
                            @if($seEncontro)
                                encontrado
                            @elseif($malEscrito)
                                número mal escrito
                            @else
                                no se encontró
                            @endif
                            @if($seEncontro)
                                <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $r['fuente'] }}</span>
                            @endif
                        </p>
                        <dl class="mt-2 space-y-1 text-xs">
                            @foreach($r as $campo => $valor)
                                @continue(in_array($campo, ['valido', 'numero', 'tipo', 'fuente'], true) || $valor === null)
                                <div class="flex gap-2">
                                    <dt class="w-24 shrink-0 text-gray-500">{{ str_replace('_', ' ', $campo) }}</dt>
                                    <dd class="min-w-0 font-medium text-gray-800">{{ $valor }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @else
                    <p class="flex h-full items-center justify-center text-center text-xs text-gray-400">
                        Escribe un número y pulsa «Probar»:<br>aquí sale lo que conteste.
                    </p>
                @endif
            </div>
        </div>
    </section>


    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900"
                title="Se descarga de SUNAT y se importa a una tabla aparte; el padrón en uso solo se sustituye al final, cuando la nueva está completa. Las consultas no se cortan en ningún momento.">
                Actualizar
            </h2>
            <p class="mt-0.5 text-xs text-gray-500">Las consultas no se cortan mientras se importa.</p>
        </div>

        @php($sinEspacio = ($espacio['libre'] ?? 99) < $espacio['necesario'])
        @php($ultima = $importaciones->first())

        {{-- Lo que impide lanzarlo va a todo el ancho: es lo primero que hay
             que leer y asi no compite con nada. --}}
        @if($sinEspacio || ! $puedeLanzar)
            <div class="space-y-3 border-b border-gray-100 px-5 py-4">
                @if($sinEspacio)
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-semibold">No hay espacio suficiente</p>
                        <p class="mt-1 text-xs">
                            Quedan {{ $espacio['libre'] }} GB libres y hacen falta unos {{ $espacio['necesario'] }}.
                            La importación fallaría a mitad.
                        </p>
                    </div>
                @endif

                @unless($puedeLanzar)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <p class="font-semibold">Este servidor no deja arrancar procesos</p>
                        <p class="mt-1 text-xs">Hay que lanzarlo a mano por terminal o por cron.</p>
                    </div>
                @endunless
            </div>
        @endif

        <div class="grid lg:grid-cols-2 lg:divide-x lg:divide-gray-100">

            {{-- Izquierda: lanzarlo. --}}
            <div class="space-y-3 p-5">
                <form method="POST" action="{{ route('super-admin.padron.actualizar') }}"
                      onsubmit="return confirm('La descarga e importación tarda horas y ocupa unos 3 GB. ¿Continuar?')">
                    @csrf
                    <button type="submit"
                            :disabled="enMarcha"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-300">
                        <span x-show="!enMarcha" class="inline-flex items-center gap-2"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>{{ $filas ? 'Volver a descargar' : 'Descargar e importar' }}</span>
                        <span x-show="enMarcha" x-cloak class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                            </svg>
                            <span>En marcha…</span>
                            <span x-show="importadas" x-text="importadas.toLocaleString() + ' RUC'"></span>
                        </span>
                    </button>
                </form>

                <p class="text-xs text-gray-500">
                    O por terminal:
                    <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono">php artisan padron:actualizar</code>
                </p>
            </div>

            {{-- Derecha: lo que hay que saber antes de pulsar. Estaba solo en el
                 aviso de confirmacion, que se lee cuando uno ya ha decidido. --}}
            <dl class="space-y-2 p-5 text-sm">
                <div class="flex gap-3">
                    <dt class="w-32 shrink-0 text-gray-500">Ocupa</dt>
                    <dd class="font-medium text-gray-800">unos {{ $espacio['necesario'] }} GB</dd>
                </div>
                <div class="flex gap-3">
                    <dt class="w-32 shrink-0 text-gray-500">Tarda</dt>
                    <dd class="font-medium text-gray-800">horas</dd>
                </div>
                <div class="flex gap-3">
                    <dt class="w-32 shrink-0 text-gray-500">Espacio libre</dt>
                    <dd class="font-medium {{ $sinEspacio ? 'text-red-700' : 'text-gray-800' }}">
                        {{ $espacio['libre'] !== null ? $espacio['libre'] . ' GB' : 'no se pudo medir' }}
                    </dd>
                </div>
                <div class="flex gap-3">
                    <dt class="w-32 shrink-0 text-gray-500">Última vez</dt>
                    <dd class="font-medium text-gray-800">
                        {{ $ultima?->terminada_en
                            ? \Illuminate\Support\Carbon::parse($ultima->terminada_en)->format('d/m/Y')
                            : 'nunca' }}
                    </dd>
                </div>
            </dl>
        </div>
    </section>
    </div>

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900">Últimas actualizaciones</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3">RUC importados</th>
                        <th class="px-5 py-3">Descargado</th>
                        <th class="px-5 py-3">Empezó</th>
                        <th class="px-5 py-3">Terminó</th>
                        <th class="px-5 py-3">Tardó</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($importaciones as $i)
                        <tr>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium
                                    {{ match($i->estado) {
                                        'completada' => 'bg-green-50 text-green-700',
                                        'fallida' => 'bg-red-50 text-red-700',
                                        default => 'bg-blue-50 text-blue-700',
                                    } }}">{{ $i->estado }}</span>
                                @if($i->mensaje)
                                    <p class="mt-1 max-w-md text-xs text-red-600">{{ $i->mensaje }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ number_format($i->filas) }}</td>
                            <td class="px-5 py-3 text-gray-600">
                                {{ $i->bytes_descargados ? round($i->bytes_descargados / 1024 ** 2) . ' MB' : '—' }}
                            </td>
                            <td class="px-5 py-3 text-gray-600">
                                {{ $i->iniciada_en ? \Illuminate\Support\Carbon::parse($i->iniciada_en)->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td class="px-5 py-3 text-gray-600">
                                {{ $i->terminada_en ? \Illuminate\Support\Carbon::parse($i->terminada_en)->format('d/m/Y H:i') : '—' }}
                            </td>

                            {{-- Cuanto tardo de verdad. «Tarda horas» es lo unico
                                 que se sabia antes de lanzarla; con el historial
                                 se sabe que esperar en este servidor. --}}
                            <td class="px-5 py-3 text-gray-600">
                                @if($i->iniciada_en && $i->terminada_en)
                                    @php($minutos = \Illuminate\Support\Carbon::parse($i->iniciada_en)->diffInMinutes($i->terminada_en))
                                    {{ $minutos >= 60 ? intdiv($minutos, 60) . ' h ' . ($minutos % 60) . ' min' : $minutos . ' min' }}
                                @elseif($i->iniciada_en)
                                    <span class="text-blue-600">en marcha</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-gray-500">Todavía no se ha importado nunca.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

@endsection
