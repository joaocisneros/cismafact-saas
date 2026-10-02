{{-- El registro de consultas. Lo que se mira cuando un cliente dice "me falla".

     Aqui NO va cuanto gasta cada llave: eso esta en «Mis APIs», pegado a su
     llave y con su tope al lado, que es donde uno mira para saber si a alguien
     le queda cuota. Estaba en los dos sitios y era la misma cuenta dos veces. --}}
<div class="space-y-5">

<section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Últimas consultas</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                15 por página, con los errores incluidos. Un número mal escrito no gasta cuota.
            </p>
        </div>

        <div class="flex items-center gap-1 rounded-lg bg-gray-100 p-0.5 text-xs font-medium">
            <a href="{{ route('super-admin.consultas', ['tab' => 'consumo']) }}"
               class="rounded-md px-3 py-1.5 transition {{ $solo_fallos ? 'text-gray-600 hover:text-gray-900' : 'bg-white text-gray-900 shadow-sm' }}">
                Todas
            </a>
            <a href="{{ route('super-admin.consultas', ['tab' => 'consumo', 'fallos' => 1]) }}"
               class="rounded-md px-3 py-1.5 transition {{ $solo_fallos ? 'bg-white text-red-700 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                Con error
                @if($fallos_mes)
                    <span class="ml-1 rounded-full bg-red-100 px-1.5 py-0.5 text-red-700">{{ number_format($fallos_mes) }}</span>
                @endif
            </a>
        </div>
    </div>

    @include('super-admin.consultas.tabs._resumen_linea', ['r' => $resumen_externo, 'que' => 'consultas', 'coste' => true])

    <div>
        <table class="w-full table-auto divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="w-px whitespace-nowrap px-4 py-3">Fecha y hora</th>
                    <th class="w-[34%] px-4 py-3">Cliente / API</th>
                    <th class="w-[23%] px-4 py-3">Documento consultado</th>
                    <th class="w-[29%] px-4 py-3">Resultado</th>
                    <th class="w-px whitespace-nowrap px-4 py-3 text-right">Fuente / tiempo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($historial as $h)
                    <tr class="{{ $h->exito ? '' : 'bg-red-50/40' }}">
                        <td class="whitespace-nowrap px-4 py-2.5 align-top text-gray-600">
                            <p class="font-medium text-gray-700">
                                {{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d/m/Y') }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ \Illuminate\Support\Carbon::parse($h->created_at)->format('H:i:s') }}
                            </p>
                        </td>

                        {{-- La credencial, su ambiente y el plan identifican a
                             quien hizo la consulta sin repetir columnas. --}}
                        <td class="px-4 py-2.5 align-top">
                            @if($h->llave)
                                <div class="flex items-start gap-1.5">
                                    <span class="min-w-0 break-words font-medium leading-5 text-gray-900">{{ $h->llave }}</span>
                                    @if($h->entorno === 'sandbox')
                                        <span class="shrink-0 rounded-full border border-blue-200 bg-blue-50 px-1.5 py-0.5 text-xs font-medium text-blue-700">Sandbox</span>
                                    @elseif($h->entorno === 'produccion')
                                        <span class="shrink-0 rounded-full border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-xs font-medium text-emerald-700">Producción</span>
                                    @endif
                                </div>
                                <p class="mt-0.5 break-words text-xs leading-4 text-gray-400">
                                    @if($h->empresa){{ $h->empresa }} · @endif{{ $h->plan ?? 'sin plan' }}@if($h->plan_a_medida) · a convenir @elseif((float) $h->plan_precio > 0) · S/ {{ number_format($h->plan_precio, 2) }}/mes @endif
                                </p>
                            @elseif($h->empresa)
                                <span class="text-gray-900">{{ $h->empresa }}</span>
                                <p class="text-xs italic text-gray-400">su llave ya no existe</p>
                            @else
                                <span class="text-xs italic text-gray-400">llave eliminada</span>
                            @endif
                        </td>

                        {{-- Tipo, numero y titular juntos evitan tres columnas. --}}
                        <td class="px-4 py-2.5 align-top text-gray-700">
                            <div class="inline-flex items-stretch overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
                                <span class="flex items-center px-2 py-1 text-[11px] font-bold tracking-wide text-white {{ strtolower($h->tipo) === 'ruc' ? 'bg-violet-600' : 'bg-blue-600' }}">
                                    {{ strtoupper($h->tipo) }}
                                </span>
                                <span class="flex items-center border-l border-slate-200 px-2.5 py-1 font-mono text-xs font-medium tracking-wide text-slate-700">
                                    {{ $h->numero ?: '—' }}
                                </span>
                            </div>
                            @php $ficha = $h->ficha ? json_decode($h->ficha, true) : null; @endphp
                            <p class="mt-1.5 break-words leading-5 text-slate-800">{{ $ficha['nombre'] ?? '—' }}</p>
                        </td>

                        <td class="px-4 py-2.5 align-top">
                            @if($h->exito)
                                <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Éxito</span>
                            @else
                                @if($h->fuente === 'invalido')
                                    <span class="rounded-full bg-orange-50 px-2 py-0.5 text-xs font-medium text-orange-700">Número inválido</span>
                                @else
                                    <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Sin datos</span>
                                @endif
                                @if($h->motivo)
                                    <p class="mt-1 break-words text-xs leading-4 text-gray-500">{{ $h->motivo }}</p>
                                @endif
                            @endif
                        </td>

                        <td class="whitespace-nowrap px-4 py-2.5 text-right align-top text-gray-600">
                            @include('super-admin.consultas.tabs._fuente', ['fuente' => $h->fuente, 'coste' => false])
                            <p class="mt-1 text-xs text-gray-400">
                                @if($h->fuente === 'invalido' || $h->ms === null)—
                                @elseif($h->ms == 0)&lt;1 ms
                                @else{{ number_format($h->ms) }} ms
                                @endif
                            </p>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-gray-500">
                            {{ $solo_fallos ? 'Ninguna consulta ha fallado.' : 'Todavía no hay ninguna consulta.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($historial->hasPages())
        <div class="border-t border-gray-100 px-5 py-3">
            {{ $historial->onEachSide(1)->links() }}
        </div>
    @endif
</section>
</div>
