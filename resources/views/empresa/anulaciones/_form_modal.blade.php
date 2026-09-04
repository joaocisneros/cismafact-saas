<div class="p-5 space-y-4">

    <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
        Puedes anular <strong>facturas, boletas y notas</strong>. SUNAT usa un trámite distinto para cada tipo,
        pero de eso se encarga el sistema. El plazo es de <strong>{{ $diasDePlazo }} días</strong> desde la emisión;
        para algo más antiguo, emite una Nota de Crédito.
    </div>

    {{-- Paso 1: la fecha. Recarga el propio modal con los comprobantes de ese día. --}}
    <div class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Fecha en que se emitieron</label>
            <input type="date" id="fechaAnulacion" value="{{ $fecha ?? now()->toDateString() }}" max="{{ now()->toDateString() }}"
                   class="rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>
        <button type="button"
                onclick="window.openAdminModal('{{ route('empresa.anulaciones.create') }}?fecha=' + document.getElementById('fechaAnulacion').value, 'Anular comprobantes')"
                class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white">
            Buscar comprobantes
        </button>
    </div>

    {{-- Paso 2: elegir y enviar --}}
    @if($fecha)
        @if(count($documentos) === 0)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                No hay comprobantes aceptados por SUNAT con fecha
                {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}, o ya fueron anulados.
            </div>
        @else
            {{-- Se bloquea al enviar: la baja va a SUNAT y gasta correlativo, asi
                 que un segundo clic durante la espera manda otra. --}}
            <form method="POST" action="{{ route('empresa.anulaciones.store') }}" class="space-y-4"
                  x-data="{ enviando: false }" @submit="enviando = true"
                  data-success-message="Anulación enviada a SUNAT.">
                @csrf
                <input type="hidden" name="fecha_referencia" value="{{ $fecha }}">

                <div class="max-h-72 overflow-y-auto sin-barra rounded-lg border border-gray-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="w-10 px-3 py-2"></th>
                                <th class="px-3 py-2">Comprobante</th>
                                <th class="px-3 py-2">Tipo</th>
                                <th class="px-3 py-2">Sucursal</th>
                                <th class="px-3 py-2">Trámite</th>
                                <th class="px-3 py-2 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documentos as $i => $d)
                                <tr class="border-t border-gray-100">
                                    <td class="px-3 py-2">
                                        <input type="checkbox" checked
                                               name="documentos[{{ $i }}][_sel]" value="1"
                                               onchange="this.closest('tr').querySelectorAll('input[type=hidden]').forEach(h=>h.disabled=!this.checked)">
                                        <input type="hidden" name="documentos[{{ $i }}][tipo_documento]" value="{{ $d['tipo_documento'] }}">
                                        <input type="hidden" name="documentos[{{ $i }}][serie]" value="{{ $d['serie'] }}">
                                        <input type="hidden" name="documentos[{{ $i }}][correlativo]" value="{{ $d['correlativo'] }}">
                                    </td>
                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $d['numero_completo'] }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $d['tipo_nombre'] }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $d['sucursal'] ?? '—' }}</td>
                                    <td class="px-3 py-2">
                                        <span class="rounded px-2 py-0.5 text-xs {{ $d['via'] === 'Resumen de boletas' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $d['via'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-700">S/ {{ number_format($d['monto'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="text-xs text-gray-500">
                    «Trámite» es informativo: SUNAT anula las boletas por resumen y el resto por comunicación
                    de baja. El sistema envía cada grupo por donde corresponde.
                </p>

                <label class="block text-sm font-medium text-gray-700">Motivo de la anulación
                    <input name="motivo" value="{{ old('motivo') }}" required minlength="3" maxlength="250"
                           placeholder="Ej.: error en los datos del cliente"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </label>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" onclick="window.closeAdminModal()"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cancelar</button>
                    <button type="submit" :disabled="enviando"
                            class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                            onclick="return confirm('¿Anular los comprobantes marcados? Se comunica a SUNAT y no se puede deshacer.')">
                        <span x-text="enviando ? 'Enviando a SUNAT…' : 'Anular en SUNAT'"></span>
                    </button>
                </div>
            </form>
        @endif
    @endif
</div>
