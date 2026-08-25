@extends('layouts.app')

@section('title', 'Consulta CPE')

@section('content')
<div class="space-y-6">
    @php
        // La Consulta de Validez de SUNAT solo existe en produccion: pregunta si
        // un comprobante es valido en los registros reales, y los de prueba no
        // estan ahi. Se avisa antes de pulsar, no despues, y con el enlace a
        // donde se arregla.
        $empresaCpe = auth()->user()->company;
        $disponibleCpe = (bool) $empresaCpe?->modo_produccion;
        $faltanCredenciales = $disponibleCpe && ! ($empresaCpe->api_sunat_client_id && $empresaCpe->api_sunat_client_secret);
    @endphp

    <div>
        <h1 class="text-2xl font-bold text-gray-800">Consulta CPE</h1>
        <p class="text-gray-500 mt-1">Verifica en SUNAT el estado real de tus comprobantes emitidos.</p>
    </div>

    @unless($disponibleCpe)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">Disponible solo en producción</p>
            <p class="mt-1">
                Esta consulta le pregunta a SUNAT si un comprobante es válido en sus registros reales.
                Tu empresa está en <strong>modo pruebas</strong>, donde los comprobantes no se registran ahí,
                así que SUNAT no ofrece este servicio.
            </p>
            <p class="mt-2">
                Se activará solo cuando pases a producción.
                <a href="{{ route('empresa.sunat-config.index') }}" class="font-medium underline">Ir a Configuración SUNAT</a>
            </p>
        </div>
    @elseif($faltanCredenciales)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">Faltan las credenciales de la API de SUNAT</p>
            <p class="mt-1">
                Estás en producción, pero esta consulta necesita además un <strong>client id</strong> y un
                <strong>client secret</strong> que se generan desde tu Clave SOL
                (Menú SOL → Empresas → Servicios web). Son distintos del certificado y del usuario secundario.
            </p>
            <p class="mt-2">
                <a href="{{ route('empresa.sunat-config.index') }}" class="font-medium underline">Registrarlas en Configuración SUNAT</a>
            </p>
        </div>
    @endunless

    @if($resultado = session('consulta_resultado'))
        <div class="rounded-lg border px-4 py-3 text-sm {{ $resultado['success'] ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' }}">
            <p class="font-semibold">Resultado de la consulta — {{ $resultado['documento'] }}</p>
            <p class="mt-1">{{ $resultado['message'] }}</p>
            @if(!empty($resultado['data']) && is_array($resultado['data']))
                <div class="mt-2 text-xs">
                    @foreach($resultado['data'] as $k => $v)
                        <span class="inline-block mr-3"><span class="font-medium">{{ $k }}:</span> {{ is_scalar($v) ? $v : json_encode($v) }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @php
        $estadoBadge = function ($estado) {
            return match ($estado) {
                'ACEPTADO' => 'bg-green-100 text-green-700',
                'RECHAZADO' => 'bg-red-100 text-red-700',
                'PENDIENTE', 'PROCESANDO' => 'bg-yellow-100 text-yellow-700',
                default => 'bg-gray-100 text-gray-600',
            };
        };
    @endphp

    @foreach([['docs' => $invoices, 'tipo' => '01', 'titulo' => 'Facturas'], ['docs' => $boletas, 'tipo' => '03', 'titulo' => 'Boletas']] as $bloque)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-3 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700">{{ $bloque['titulo'] }} recientes</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Documento</th>
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3">Monto</th>
                        <th class="px-5 py-3">Estado actual</th>
                        <th class="px-5 py-3 text-right">SUNAT</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($bloque['docs'] as $doc)
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $doc->numero_completo ?? ($doc->serie.'-'.$doc->correlativo) }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ \Carbon\Carbon::parse($doc->fecha_emision)->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 text-gray-600">S/ {{ number_format($doc->mto_imp_venta, 2) }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $estadoBadge($doc->estado_sunat) }}">{{ $doc->estado_sunat ?? 'SIN ENVIAR' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('empresa.consulta-cpe.consultar') }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="tipo_documento" value="{{ $bloque['tipo'] }}">
                                    <input type="hidden" name="documento_id" value="{{ $doc->id }}">
                                    <button @disabled(! $disponibleCpe)
                                            title="{{ $disponibleCpe ? 'Consultar en SUNAT' : 'Disponible solo en producción' }}"
                                            class="{{ $disponibleCpe ? 'text-blue-600 hover:underline' : 'cursor-not-allowed text-gray-400' }}">Consultar estado</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-5 py-4 text-center text-gray-500" colspan="5">No hay {{ strtolower($bloque['titulo']) }} para consultar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach
</div>
@endsection
