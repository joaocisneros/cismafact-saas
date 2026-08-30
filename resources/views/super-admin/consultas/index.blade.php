@extends('layouts.app')

@section('title', 'API RUC y DNI')

@section('content')
<div class="space-y-6">

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    {{-- El consumo primero: es lo que se cobra y lo que hay que vigilar. --}}
    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">Servidas este mes</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($mes['total']) }}</p>
            <p class="mt-1 text-xs text-gray-500">
                {{ number_format($mes['ruc']) }} RUC · {{ number_format($mes['dni']) }} DNI
            </p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">Resueltas en casa</p>
            <p class="mt-1 text-2xl font-semibold text-green-700">{{ number_format($mes['en_casa']) }}</p>
            <p class="mt-1 text-xs text-gray-500">
                @if($mes['total'])
                    {{ round($mes['en_casa'] / $mes['total'] * 100) }}% sin salir a internet
                @else
                    Sin consultas todavía
                @endif
            </p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">Fueron al proveedor</p>
            <p class="mt-1 text-2xl font-semibold {{ $mes['al_proveedor'] ? 'text-amber-700' : 'text-gray-400' }}">
                {{ number_format($mes['al_proveedor']) }}
            </p>
            <p class="mt-1 text-xs text-gray-500">Lo que te cuesta de verdad</p>
        </div>

        <a href="{{ route('super-admin.padron') }}"
           class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-gray-300 hover:shadow-md">
            <p class="text-xs text-gray-500">Fichas en casa</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($guardadas['total'] + $padron) }}</p>
            <p class="mt-1 text-xs text-gray-500">
                {{ $padron ? number_format($padron) . ' del padrón' : 'Padrón sin importar' }}
            </p>
        </a>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900">Lo que le pasas a tus clientes</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Entran con la misma clave con la que emiten. No hay que darles nada nuevo.
            </p>
        </div>

        <div class="space-y-3 p-5">
            @foreach([
                ['/api/consultas/ruc/{numero}', 'Razón social, estado, condición y domicilio fiscal', url('/api/consultas/ruc/20100070970')],
                ['/api/consultas/dni/{numero}', 'Nombre completo y apellidos por separado', url('/api/consultas/dni/46756431')],
                ['/api/consultas/cuota', 'Cuánto lleva consumido y cuánto le queda este mes', null],
            ] as [$ruta, $devuelve, $ejemplo])
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">GET</span>
                        <code class="font-mono text-sm text-gray-900">{{ $ruta }}</code>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-600">{{ $devuelve }}</p>
                    @if($ejemplo)
                        <p class="mt-1 break-all font-mono text-xs text-gray-400">{{ $ejemplo }}</p>
                    @endif
                </div>
            @endforeach

            <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-xs leading-relaxed text-blue-900">
                <p><strong>Cabeceras:</strong> <code>X-Api-Key</code> y <code>X-Api-Secret</code>, las mismas de emisión.</p>
                <p class="mt-1"><strong>Una sola bolsa</strong> para RUC y DNI: quien solo factura a empresas no
                    desperdicia las de DNI.</p>
                <p class="mt-1"><strong>Un número mal escrito no gasta cuota</strong> — responde <code>422</code>
                    sin llegar a preguntar a nadie.</p>
                <p class="mt-1"><strong>Al agotarse la cuota</strong> responde <code>429</code>.
                    Y hay un tope de <strong>30 por minuto</strong>, para que una ráfaga no tumbe el servicio.</p>
            </div>
        </div>
    </section>

    {{-- Las cuotas, aqui mismo: quien mira el consumo es quien decide el tope. --}}
    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900">Cuántas consultas incluye cada plan</h2>
            <p class="mt-0.5 text-xs text-gray-500">Al mes, compartidas entre RUC y DNI. Un 0 deja el plan sin consultas.</p>
        </div>

        <form method="POST" action="{{ route('super-admin.consultas.cuotas') }}" class="p-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($planes as $plan)
                    <div class="rounded-lg border border-gray-200 p-4">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="text-sm font-semibold text-gray-900">{{ $plan->name }}</p>
                            <p class="text-xs text-gray-500">S/ {{ number_format($plan->monthly_price, 2) }}</p>
                        </div>
                        <label for="cuota_{{ $plan->id }}" class="mt-3 mb-1 block text-xs text-gray-500">Consultas al mes</label>
                        <input type="number" min="0" max="1000000" id="cuota_{{ $plan->id }}"
                               name="cuotas[{{ $plan->id }}]"
                               value="{{ old('cuotas.' . $plan->id, $plan->consultas_limit) }}"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1.5 text-xs text-gray-400">
                            {{ number_format($plan->monthly_document_limit) }} comprobantes ·
                            {{ number_format($plan->api_request_limit) }} peticiones
                        </p>
                    </div>
                @endforeach
            </div>

            <button type="submit" class="mt-4 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700">
                Guardar cuotas
            </button>
        </form>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900">Quién consulta este mes</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Empresa</th>
                        <th class="px-5 py-3">Plan</th>
                        <th class="px-5 py-3">Consultas</th>
                        <th class="px-5 py-3">Al proveedor</th>
                        <th class="w-52 px-5 py-3">Cuota</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($empresas as $e)
                        @php
                            $tope = (int) $e->tope;
                            $pct = $tope > 0 ? min(100, round($e->usadas / $tope * 100)) : 0;
                        @endphp
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900">{{ $e->razon_social }}</p>
                                <p class="font-mono text-xs text-gray-500">{{ $e->ruc }}</p>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $e->plan ?? '—' }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ number_format($e->usadas) }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ number_format($e->al_proveedor) }}</td>
                            <td class="px-5 py-3">
                                @if($tope > 0)
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                                        <div class="h-full rounded-full {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-amber-500' : 'bg-green-500') }}"
                                             style="width: {{ $pct }}%"></div>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">{{ number_format($e->usadas) }} de {{ number_format($tope) }}</p>
                                @else
                                    <span class="text-xs text-gray-400">Sin plan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-gray-500">Nadie ha consultado este mes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900">De dónde salen los datos</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                SUNAT no publica una API abierta: lo que hay son revendedores del padrón.
                Cuando importes el padrón, esto deja de hacer falta para el RUC.
            </p>
        </div>

        <form method="POST" action="{{ route('super-admin.consultas.update') }}" class="space-y-4 p-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="consultas_url" class="mb-1 block text-sm font-medium text-gray-700">Dirección</label>
                    <input type="url" name="consultas_url" id="consultas_url"
                           value="{{ old('consultas_url', $ajustes['consultas_url'] ?? '') }}"
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 font-mono text-sm outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="https://api.apis.net.pe/v1/{tipo}?numero={numero}">
                    <p class="mt-1 text-xs text-gray-500">
                        Pon <code class="rounded bg-gray-100 px-1">{tipo}</code> y
                        <code class="rounded bg-gray-100 px-1">{numero}</code> donde vayan.
                        Sin marcas se añade <code class="rounded bg-gray-100 px-1">/ruc/&lt;número&gt;</code> al final.
                    </p>
                </div>
                <div>
                    <label for="consultas_token" class="mb-1 block text-sm font-medium text-gray-700">Token</label>
                    <input type="password" name="consultas_token" id="consultas_token" autocomplete="new-password"
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="{{ !empty($ajustes['consultas_token']) ? 'Dejar vacío para mantener el actual' : 'Opcional' }}">
                    <p class="mt-1 text-xs text-gray-500">Viaja como <code class="rounded bg-gray-100 px-1">Authorization: Bearer</code></p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700">
                    Guardar proveedor
                </button>
                <p class="text-xs text-gray-500">Sin proveedor se sigue validando el número, pero no llega el nombre.</p>
            </div>
        </form>

        <div class="border-t border-gray-100 bg-gray-50 p-5">
            <form method="POST" action="{{ route('super-admin.consultas.probar') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label for="tipo" class="mb-1 block text-xs font-medium text-gray-700">Probar</label>
                    <select name="tipo" id="tipo" class="rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="ruc">RUC</option>
                        <option value="dni">DNI</option>
                    </select>
                </div>
                <input type="text" name="numero" value="{{ old('numero') }}"
                       class="w-48 rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="20608251589">
                <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                    Consultar
                </button>
                <p class="text-xs text-gray-500">Salta lo guardado y pregunta de verdad.</p>
            </form>

            @if($r = session('consulta_prueba'))
                <div class="mt-4 rounded-lg border bg-white px-4 py-3 text-sm {{ $r['valido'] ? 'border-green-200' : 'border-red-200' }}">
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

    @if($guardadas['total'] > 0)
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-gray-900">
                        {{ number_format($guardadas['total']) }} fichas guardadas de consultas anteriores
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500">
                        Vaciarlas hace que se vuelvan a pedir al proveedor. Útil si un dato cambió en SUNAT.
                    </p>
                </div>
                <form method="POST" action="{{ route('super-admin.consultas.cache.vaciar') }}"
                      onsubmit="return confirm('Se borrarán {{ $guardadas['total'] }} fichas. ¿Continuar?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50">
                        Vaciar
                    </button>
                </form>
            </div>
        </section>
    @endif

</div>
@endsection
