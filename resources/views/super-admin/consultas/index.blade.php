@extends('layouts.app')

@section('title', 'API RUC y DNI')

@section('content')
<div class="space-y-5">

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    {{-- Una franja, no cuatro tarjetas: eran cuatro cifras para decir casi lo
         mismo y competian entre ellas. --}}
    <section class="flex flex-wrap items-center gap-x-8 gap-y-3 rounded-lg border border-gray-200 bg-white px-5 py-4 shadow-sm">
        <div>
            <p class="text-2xl font-semibold leading-none text-gray-900">{{ number_format($mes['total']) }}</p>
            <p class="mt-1 text-xs text-gray-500">consultas este mes</p>
        </div>

        <div class="h-8 w-px bg-gray-200"></div>

        <div>
            <p class="text-2xl font-semibold leading-none text-amber-700">{{ number_format($mes['al_proveedor']) }}</p>
            <p class="mt-1 text-xs text-gray-500">fueron al proveedor</p>
        </div>

        <div>
            <p class="text-2xl font-semibold leading-none text-green-700">{{ number_format($mes['en_casa']) }}</p>
            <p class="mt-1 text-xs text-gray-500">
                salieron de tu base
                @if($mes['total']) ({{ round($mes['en_casa'] / $mes['total'] * 100) }}%) @endif
            </p>
        </div>

        <a href="{{ route('super-admin.padron') }}"
           class="ml-auto rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-600 transition hover:bg-gray-50">
            Padrón:
            <span class="font-semibold text-gray-900">{{ $padron ? number_format($padron) . ' RUC' : 'sin importar' }}</span>
            <span class="text-gray-400">→</span>
        </a>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-900">Endpoints para tus clientes</h2>
        </div>

        <div class="divide-y divide-gray-100">
            @foreach([
                ['/api/consultas/ruc/{numero}', 'Razón social, estado, condición y domicilio fiscal'],
                ['/api/consultas/dni/{numero}', 'Nombre completo y apellidos por separado'],
                ['/api/consultas/cuota', 'Lo que lleva consumido y lo que le queda'],
            ] as [$ruta, $devuelve])
                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-5 py-3">
                    <span class="rounded bg-green-100 px-1.5 py-0.5 text-[11px] font-semibold text-green-800">GET</span>
                    <code class="font-mono text-sm text-gray-900">{{ $ruta }}</code>
                    <span class="text-xs text-gray-500">{{ $devuelve }}</span>
                </div>
            @endforeach
        </div>

        <p class="border-t border-gray-100 bg-gray-50 px-5 py-3 text-xs text-gray-500">
            Con <code class="rounded bg-white px-1">X-Api-Key</code> y
            <code class="rounded bg-white px-1">X-Api-Secret</code>, las mismas de emisión ·
            RUC y DNI comparten cuota · un número mal escrito no la gasta ·
            máximo 30 por minuto
        </p>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-900">Consultas incluidas al mes</h2>
        </div>

        <form method="POST" action="{{ route('super-admin.consultas.cuotas') }}"
              class="flex flex-wrap items-end gap-4 px-5 py-4">
            @csrf
            @method('PUT')

            @foreach($planes as $plan)
                <div>
                    <label for="cuota_{{ $plan->id }}" class="mb-1 block text-xs font-medium text-gray-700">
                        {{ $plan->name }}
                    </label>
                    <input type="number" min="0" max="1000000" id="cuota_{{ $plan->id }}"
                           name="cuotas[{{ $plan->id }}]"
                           value="{{ old('cuotas.' . $plan->id, $plan->consultas_limit) }}"
                           class="w-28 rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            @endforeach

            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                Guardar
            </button>
        </form>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-900">Quién consulta este mes</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-2.5">Empresa</th>
                        <th class="px-5 py-2.5">Plan</th>
                        <th class="px-5 py-2.5">Consultas</th>
                        <th class="px-5 py-2.5">Al proveedor</th>
                        <th class="w-48 px-5 py-2.5">Cuota</th>
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

    <p class="text-xs text-gray-500">
        Los datos salen de
        <a href="{{ route('super-admin.padron') }}" class="font-medium text-blue-600 hover:underline">
            {{ $padron ? 'tu padrón local' : ($ajustes['consultas_url'] ?? false ? parse_url($ajustes['consultas_url'], PHP_URL_HOST) : 'ningún sitio todavía') }}
        </a>.
    </p>

</div>
@endsection
