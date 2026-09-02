@extends('layouts.app')

@section('title', 'API Facturación')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">API Facturación</h1>
            <p class="mt-1 text-sm text-gray-500">Consumo de cada empresa y control de su acceso.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('super-admin.tokens-prueba.index') }}"
               class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                Sandbox Facturación
            </a>
            <a href="{{ route('super-admin.api-global.logs') }}"
               class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" title="Registro cronológico de cada llamada, con filtros">
                Registro de llamadas
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Cifras del servicio, en un solo bloque. --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <dl class="grid grid-cols-2 divide-gray-200 sm:grid-cols-4 sm:divide-x">
            @foreach([
                ['Solicitudes hoy', number_format($consumoHoy), false],
                ['Solicitudes del mes', number_format($consumoMes), false],
                ['Errores hoy', number_format($erroresHoy), $erroresHoy > 0],
                ['Tiempo de respuesta', number_format($tiempoPromedio, 0) . ' ms', false],
            ] as [$titulo, $valor, $alerta])
                <div class="px-5 py-4">
                    <dt class="text-xs font-medium uppercase text-gray-500">{{ $titulo }}</dt>
                    <dd class="mt-1 text-2xl font-semibold {{ $alerta ? 'text-red-600' : 'text-gray-900' }}">{{ $valor }}</dd>
                </div>
            @endforeach
        </dl>
        <div class="flex flex-wrap items-center gap-x-4 border-t border-gray-100 bg-gray-50 px-5 py-2.5 text-xs text-gray-600">
            <span class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-green-500"></span> Servicio disponible
            </span>
            <span>{{ number_format($apiKeyActivas) }} credenciales activas</span>
        </div>
    </div>

    {{-- Consumo por empresa: quién está cerca de su tope y a quién cortar. --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-semibold text-gray-900">Consumo por empresa</h2>
            <p class="mt-0.5 text-sm text-gray-500">
                Llamadas de este mes contra el tope de su plan. El contador se reinicia el día 1.
            </p>
        </div>

        @if($empresas->isEmpty())
            <p class="px-5 py-12 text-center text-sm text-gray-500">Todavía no hay empresas registradas.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Empresa</th>
                            <th class="px-4 py-3">Plan</th>
                            <th class="w-64 px-4 py-3">Consumo del mes</th>
                            <th class="px-4 py-3">Llamadas hoy</th>
                            <th class="px-4 py-3">Último uso</th>
                            <th class="px-4 py-3">Credenciales</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($empresas as $e)
                            @php
                                $empresa = $e['modelo'];
                                $tieneAcceso = $e['activas'] > 0;
                                $tono = $e['porcentaje'] >= 90 ? 'bg-red-500' : ($e['porcentaje'] >= 70 ? 'bg-amber-500' : 'bg-blue-500');
                            @endphp
                            <tr class="border-t border-gray-100 hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-gray-900">{{ $empresa->razon_social }}</p>
                                    <p class="font-mono text-xs text-gray-400">{{ $empresa->ruc }}</p>
                                </td>

                                <td class="px-4 py-3 text-gray-600">{{ $e['plan'] }}</td>

                                <td class="px-4 py-3">
                                    @if($e['ilimitado'])
                                        <p class="text-gray-700">{{ number_format($e['usado']) }} <span class="text-gray-400">· sin tope</span></p>
                                    @else
                                        <div class="flex items-baseline justify-between gap-2">
                                            <span class="font-semibold text-gray-900">{{ number_format($e['usado']) }}</span>
                                            <span class="text-xs text-gray-500">de {{ number_format($e['limite']) }}</span>
                                        </div>
                                        <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                            <div class="h-2 rounded-full {{ $tono }}" style="width: {{ max(2, $e['porcentaje']) }}%"></div>
                                        </div>
                                        @if($e['porcentaje'] >= 90)
                                            <p class="mt-1 text-xs font-medium text-red-600">
                                                {{ $e['usado'] >= $e['limite'] ? 'Alcanzó su tope' : 'Al ' . $e['porcentaje'] . '% de su tope' }}
                                            </p>
                                        @endif
                                    @endif
                                </td>

                                {{-- Las de hoy, no las del mes: el consumo del mes ya
                                     sale en la columna de al lado con su barra, y
                                     repetirlo dejaba dos columnas con el mismo numero.
                                     Lo que no se veia es si hoy hay movimiento.

                                     Los errores del mes van aqui tambien: es donde se
                                     mira cuando algo va mal. --}}
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-900">{{ number_format($e['hoy']) }}</span>
                                    @if($e['errores'] > 0)
                                        <span class="ml-1 text-xs font-semibold text-red-600">{{ number_format($e['errores']) }} con error</span>
                                    @endif
                                </td>

                                {{-- Una credencial habilitada que no se usa nunca es
                                     una integracion que no arranco. No se veia. --}}
                                <td class="px-4 py-3 text-gray-600">
                                    @if($e['ultimo_uso'])
                                        <span title="{{ \Carbon\Carbon::parse($e['ultimo_uso'])->format('d/m/Y H:i') }}">
                                            {{ \Carbon\Carbon::parse($e['ultimo_uso'])->diffForHumans(short: true) }}
                                        </span>
                                    @elseif($e['credenciales'] > 0)
                                        <span class="text-xs text-amber-600">nunca</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>

                                {{-- Credenciales y acceso eran dos columnas que decian
                                     lo mismo: el acceso se calcula de si hay alguna
                                     activa, asi que «1 de 1» ya implicaba «Habilitado». --}}
                                <td class="px-4 py-3">
                                    @if($e['credenciales'] === 0)
                                        <span class="text-xs text-gray-400">sin credenciales</span>
                                    @elseif($tieneAcceso)
                                        <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                                            {{ $e['activas'] }} activa{{ $e['activas'] > 1 ? 's' : '' }}
                                        </span>
                                    @else
                                        <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">cortada{{ $e['credenciales'] > 1 ? 's' : '' }}</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <x-icon-action icon="ver" label="Ver credenciales de esta empresa" color="blue" type="button"
                                                       onclick="window.openAdminModal('{{ route('super-admin.api-global.api-keys', ['company_id' => $empresa->id]) }}', 'Credenciales de la empresa')" />

                                        {{-- Generar el secret sin entrar, cuando la empresa
                                             tiene una sola credencial: es el caso normal y
                                             ahorra abrir la ficha para una accion de un clic.
                                             Con varias no se pone: el boton no sabria a cual
                                             de ellas se refiere, y hay que entrar a elegir. --}}
                                        @if($e['unica'])
                                            <form method="POST" action="{{ route('super-admin.api-global.regenerate-key', $e['unica']) }}"
                                                  data-success-message="Secret regenerado. Ábrelo con «Mostrar» y pásaselo al cliente."
                                                  @submit.prevent="if (confirm('Se genera un Secret nuevo para ' + @js($empresa->razon_social) + '. El actual deja de funcionar al instante, así que hay que pasarle el nuevo. La X-Api-Key no cambia. ¿Seguir?')) window.enviarYAbrirModal($el, '{{ route('super-admin.api-global.api-keys', ['company_id' => $empresa->id]) }}', 'Credenciales de la empresa')">
                                                @csrf
                                                <x-icon-action icon="renovar" label="Generar un Secret nuevo" color="amber" />
                                            </form>
                                        @endif

                                        @if($e['credenciales'] > 0)
                                            <form method="POST" action="{{ route('super-admin.api-global.toggle-company', $empresa) }}"
                                                  onsubmit="return confirm('{{ $tieneAcceso ? 'Cortar el acceso por API de esta empresa? Sus integraciones dejaran de emitir al instante.' : 'Restablecer el acceso por API de esta empresa?' }}')">
                                                @csrf
                                                <x-icon-action :icon="$tieneAcceso ? 'bloquear' : 'desbloquear'"
                                                               :label="$tieneAcceso ? 'Cortar acceso por API' : 'Restablecer acceso'"
                                                               :color="$tieneAcceso ? 'amber' : 'emerald'" />
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
