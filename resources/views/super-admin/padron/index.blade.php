@extends('layouts.app')

@section('title', 'Padrón SUNAT')

@section('content')
<div class="space-y-6"
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

    <section class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">RUC en el padrón local</p>
            <p class="mt-1 text-2xl font-semibold {{ $filas ? 'text-gray-900' : 'text-gray-400' }}"
               x-text="filas.toLocaleString('es-PE')">{{ number_format($filas) }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $filas ? 'Responde sin salir a internet' : 'Sin importar todavía' }}</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">Espacio libre en disco</p>
            <p class="mt-1 text-2xl font-semibold {{ ($espacio['libre'] ?? 0) >= $espacio['necesario'] ? 'text-gray-900' : 'text-red-700' }}">
                {{ $espacio['libre'] !== null ? $espacio['libre'] . ' GB' : '—' }}
            </p>
            <p class="mt-1 text-xs text-gray-500">Hacen falta unos {{ $espacio['necesario'] }} GB</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">Estado</p>
            <p class="mt-1 text-sm font-semibold" :class="enMarcha ? 'text-blue-700' : 'text-gray-900'">
                <span x-show="!enMarcha">{{ $filas ? '● Al día' : '○ Vacío' }}</span>
                <span x-show="enMarcha" x-cloak>● En marcha</span>
            </p>
            <p class="mt-1 text-xs text-gray-500" x-show="enMarcha" x-cloak>
                <span x-text="importadas.toLocaleString('es-PE')"></span> importados
            </p>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900">Qué es esto</h2>
        </div>
        <div class="space-y-3 p-5 text-sm text-gray-700">
            <p>
                Una copia del <strong>padrón reducido</strong> que SUNAT publica. Con ella, consultar un RUC
                no sale a internet: se responde desde la propia base, sin límite y en milisegundos.
            </p>
            <p class="text-gray-600">
                <strong class="text-gray-800">Trae lo mismo que un proveedor externo</strong> —nombre, estado,
                condición y domicilio— y nada más. La fecha de inscripción, la actividad económica y el tipo de
                contribuyente solo están en la ficha web de SUNAT, detrás de un captcha. Esto da independencia,
                no información nueva.
            </p>
            <p class="text-gray-600">
                <strong class="text-gray-800">Y envejece.</strong> Es una foto del día en que SUNAT publicó el
                archivo: un RUC inscrito después no figura, y uno dado de baja ayer sigue apareciendo como
                activo. Por eso el proveedor externo no sobra: el padrón responde primero y él cubre lo que falte.
            </p>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900">Actualizar</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Se descarga de SUNAT y se importa a una tabla aparte; el padrón en uso solo se sustituye
                al final, cuando la nueva está completa. Las consultas no se cortan en ningún momento.
            </p>
        </div>

        <div class="space-y-4 p-5">
            @if(($espacio['libre'] ?? 99) < $espacio['necesario'])
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

            <div class="flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('super-admin.padron.actualizar') }}"
                      onsubmit="return confirm('La descarga e importación tarda horas y ocupa unos 3 GB. ¿Continuar?')">
                    @csrf
                    <button type="submit"
                            :disabled="enMarcha"
                            class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-300">
                        <span x-show="!enMarcha">{{ $filas ? 'Volver a descargar' : 'Descargar e importar' }}</span>
                        <span x-show="enMarcha" x-cloak>En marcha…</span>
                    </button>
                </form>

                <p class="text-xs text-gray-500">
                    O por terminal:
                    <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono">php artisan padron:actualizar</code>
                </p>
            </div>
        </div>
    </section>

    {{-- La otra fuente del mismo dato. Mientras el padron este vacio es la
         unica; cuando se importe, pasa a cubrir solo lo que el padron no tenga
         (los RUC inscritos despues de la ultima descarga). --}}
    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900">Proveedor externo</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                @if($filas)
                    Cubre lo que el padrón no tenga: los RUC inscritos después de la última descarga.
                @else
                    Mientras el padrón esté vacío, todo sale de aquí.
                @endif
            </p>
        </div>

        <form method="POST" action="{{ route('super-admin.padron.proveedor') }}" class="space-y-3 px-5 py-4">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="consultas_url" class="mb-1 block text-xs font-medium text-gray-700">Dirección</label>
                    <input type="url" name="consultas_url" id="consultas_url"
                           value="{{ old('consultas_url', $ajustes['consultas_url'] ?? '') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="https://api.apis.net.pe/v1/{tipo}?numero={numero}">
                    <p class="mt-1 text-xs text-gray-500">
                        Pon <code class="rounded bg-gray-100 px-1">{tipo}</code> y
                        <code class="rounded bg-gray-100 px-1">{numero}</code> donde vayan.
                    </p>
                </div>
                <div>
                    <label for="consultas_token" class="mb-1 block text-xs font-medium text-gray-700">Token</label>
                    <input type="password" name="consultas_token" id="consultas_token" autocomplete="new-password"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="{{ !empty($ajustes['consultas_token']) ? 'Dejar vacío para mantener el actual' : 'Opcional' }}">
                    <p class="mt-1 text-xs text-gray-500">Va como <code class="rounded bg-gray-100 px-1">Bearer</code></p>
                </div>
            </div>

            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                Guardar
            </button>
        </form>

        <div class="border-t border-gray-100 bg-gray-50 px-5 py-4">
            <form method="POST" action="{{ route('super-admin.padron.probar') }}" class="flex flex-wrap items-center gap-2">
                @csrf
                <select name="tipo" class="rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="ruc">RUC</option>
                    <option value="dni">DNI</option>
                </select>
                <input type="text" name="numero" value="{{ old('numero') }}"
                       class="w-44 rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="20608251589">
                <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                    Probar
                </button>
                <span class="text-xs text-gray-500">Pregunta de verdad, sin usar lo guardado.</span>
            </form>

            @if($r = session('consulta_prueba'))
                <div class="mt-3 rounded-lg border bg-white px-4 py-3 text-sm {{ $r['valido'] ? 'border-green-200' : 'border-red-200' }}">
                    <p class="font-semibold {{ $r['valido'] ? 'text-green-800' : 'text-red-800' }}">
                        {{ $r['numero'] }} — {{ $r['valido'] ? 'válido' : 'no válido' }}
                        @if(!empty($r['fuente']))
                            <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $r['fuente'] }}</span>
                        @endif
                    </p>
                    <dl class="mt-2 grid gap-x-6 gap-y-1 text-xs sm:grid-cols-2">
                        @foreach($r as $campo => $valor)
                            @continue(in_array($campo, ['valido', 'numero', 'tipo', 'fuente'], true) || $valor === null)
                            <div class="flex gap-2">
                                <dt class="w-28 shrink-0 text-gray-500">{{ str_replace('_', ' ', $campo) }}</dt>
                                <dd class="font-medium text-gray-800">{{ $valor }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif
        </div>
    </section>

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
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-gray-500">Todavía no se ha importado nunca.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</div>
@endsection
