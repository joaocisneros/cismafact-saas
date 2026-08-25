@php
    $cls = match($anulacion->estado_sunat) {
        'ACEPTADO' => 'bg-green-50 text-green-700',
        'RECHAZADO', 'ERROR' => 'bg-red-50 text-red-700',
        'ENVIADO', 'PROCESANDO' => 'bg-blue-50 text-blue-700',
        default => 'bg-amber-50 text-amber-700',
    };
    $tipos = ['01' => 'Factura', '03' => 'Boleta', '07' => 'Nota de Crédito', '08' => 'Nota de Débito'];
    $respuesta = json_decode($anulacion->respuesta_sunat ?? '', true);
@endphp

<div class="space-y-4 p-5">
    <div class="flex flex-wrap items-center gap-2 text-xs">
        <span class="rounded px-2 py-1 font-medium {{ $cls }}">{{ $anulacion->estado_sunat }}</span>
        <span class="rounded bg-slate-100 px-2 py-1 text-slate-700">Comunicación de baja</span>
        <span class="text-gray-500">
            Comprobantes del {{ \Illuminate\Support\Carbon::parse($anulacion->fecha_referencia)->format('d/m/Y') }}
        </span>
    </div>

    <div>
        <h3 class="font-semibold text-gray-900">
            RA-{{ \Illuminate\Support\Carbon::parse($anulacion->fecha_generacion)->format('Ymd') }}-{{ $anulacion->correlativo }}
        </h3>
        <p class="mt-1 text-sm text-gray-600">{{ $anulacion->motivo }}</p>
        @if($anulacion->ticket)
            <p class="mt-1 text-xs text-gray-400">Ticket SUNAT: {{ $anulacion->ticket }}</p>
        @endif
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-3 py-2">Comprobante</th>
                    <th class="px-3 py-2">Tipo</th>
                </tr>
            </thead>
            <tbody>
                @foreach((array) $anulacion->detalles as $d)
                    <tr class="border-t border-gray-100">
                        <td class="px-3 py-2 font-medium text-gray-900">{{ $d['serie'] ?? '' }}-{{ $d['correlativo'] ?? '' }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $tipos[$d['tipo_documento'] ?? ''] ?? $d['tipo_documento'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($respuesta)
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
            <span class="font-medium">SUNAT respondió:</span>
            {{ $respuesta['description'] ?? $respuesta['message'] ?? json_encode($respuesta) }}
        </div>
    @endif

    <div class="flex flex-wrap justify-end gap-2">
        @if($anulacion->estado_sunat !== 'ACEPTADO' && $anulacion->ticket)
            <form method="POST" action="{{ route('empresa.anulaciones.check-status', $anulacion->id) }}"
                  data-success-message="Estado actualizado.">
                @csrf
                <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Consultar estado
                </button>
            </form>
        @endif
        <button type="button" onclick="window.closeAdminModal()"
                class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cerrar</button>
    </div>

    @if($anulacion->estado_sunat !== 'ACEPTADO')
        <p class="text-xs text-gray-500">
            Los comprobantes siguen siendo válidos hasta que SUNAT acepte esta anulación.
        </p>
    @endif
</div>
