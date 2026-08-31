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
                Las 60 más recientes, con los errores incluidos. Un número mal escrito no gasta cuota.
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

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="w-px whitespace-nowrap px-5 py-3">Fecha y hora</th>
                    <th class="whitespace-nowrap px-5 py-3">Quién consultó</th>
                    <th class="w-px whitespace-nowrap px-5 py-3">Servicio</th>
                    <th class="w-px whitespace-nowrap px-5 py-3">Número</th>
                    <th class="px-5 py-3">A nombre de</th>
                    <th class="w-px whitespace-nowrap px-5 py-3">Estado</th>
                    <th class="w-px whitespace-nowrap px-5 py-3">Origen</th>
                    <th class="w-px whitespace-nowrap px-5 py-3 text-right">Tardó</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($historial as $h)
                    <tr class="{{ $h->exito ? '' : 'bg-red-50/40' }}">
                        <td class="whitespace-nowrap px-5 py-2.5 text-gray-600">
                            {{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d/m/Y H:i:s') }}
                        </td>

                        {{-- Quien consulto. La llave puede haberse borrado y lo
                             consultado sigue contando, asi que cuando falta se
                             cae a la empresa, que sobrevive: si no, la fila no
                             identifica a nadie. --}}
                        <td class="px-5 py-2.5">
                            @if($h->llave)
                                <div class="flex items-center gap-1.5">
                                    <span class="font-medium text-gray-900">{{ $h->llave }}</span>
                                    @if($h->entorno === 'sandbox')
                                        <span class="rounded-full bg-blue-50 px-1.5 py-0.5 text-xs font-medium text-blue-700">Sandbox</span>
                                    @endif
                                </div>
                                {{-- El plan va aqui, no en columna propia: es de
                                     la llave, no de la consulta, y al lado de
                                     «Origen» parecian decir lo mismo. --}}
                                <p class="text-xs text-gray-400">
                                    @if($h->empresa){{ $h->empresa }} · @endif{{ $h->plan ?? 'sin plan' }}@if($h->plan_a_medida) · a convenir @elseif((float) $h->plan_precio > 0) · S/ {{ number_format($h->plan_precio, 2) }}/mes @endif
                                </p>
                            @elseif($h->empresa)
                                <span class="text-gray-900">{{ $h->empresa }}</span>
                                <p class="text-xs italic text-gray-400">su llave ya no existe</p>
                            @else
                                <span class="text-xs italic text-gray-400">llave eliminada</span>
                            @endif
                        </td>

                        <td class="whitespace-nowrap px-5 py-2.5 text-gray-600">{{ strtoupper($h->tipo) }}</td>

                        <td class="whitespace-nowrap px-5 py-2.5 font-mono text-xs text-gray-700">{{ $h->numero }}</td>

                        {{-- De quien es el numero: sin esto la fila decia que la
                             busqueda salio bien, pero no a quien encontro. --}}
                        <td class="px-5 py-2.5 text-gray-700">
                            @php $ficha = $h->ficha ? json_decode($h->ficha, true) : null; @endphp
                            {{ $ficha['nombre'] ?? '—' }}
                        </td>

                            <td class="whitespace-nowrap px-5 py-2.5">
                                @if($h->exito)
                                    <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Éxito</span>
                                @else
                                    {{-- Dos fallos distintos que antes salian iguales:
                                         el numero mal escrito ni se llego a consultar,
                                         y «sin ficha» es que el numero valia pero no
                                         se pudo traer nada. --}}
                                    @if($h->fuente === 'invalido')
                                        <span class="rounded-full bg-orange-50 px-2 py-0.5 text-xs font-medium text-orange-700">Número inválido</span>
                                    @else
                                        <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Sin datos</span>
                                    @endif
                                    @if($h->motivo)
                                        <p class="mt-0.5 text-xs text-gray-500">{{ $h->motivo }}</p>
                                    @endif
                                @endif
                            </td>

                        {{-- De donde salio el dato: es lo que dice si esa consulta
                             costo dinero o se resolvio en casa. --}}
                        <td class="whitespace-nowrap px-5 py-2.5">
                            @include('super-admin.consultas.tabs._fuente', ['fuente' => $h->fuente, 'coste' => false])
                        </td>

                        <td class="whitespace-nowrap px-5 py-2.5 text-right text-gray-600">
                                {{-- «0 ms» se leia como que no se habia medido. Es
                                     real: se redondea a entero y esto tarda menos
                                     de medio milisegundo. Cuando no se consulto
                                     nada, no hay tiempo que dar. --}}
                                @if($h->fuente === 'invalido')
                                    <span class="text-gray-300">—</span>
                                @elseif($h->ms === null)
                                    —
                                @elseif($h->ms == 0)
                                    <span title="Menos de un milisegundo">&lt;1 ms</span>
                                @else
                                    {{ number_format($h->ms) }} ms
                                @endif
                            </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-gray-500">
                            {{ $solo_fallos ? 'Ninguna consulta ha fallado.' : 'Todavía no hay ninguna consulta.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
</div>
