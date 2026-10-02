{{-- Lo que gastan las empresas de casa buscando un RUC o un DNI desde el panel.

     Aparte del «Consumo externo» a proposito: aquello es lo que consumen los
     clientes que pagan por consultar, y descuenta cuota. Esto no se cobra, pero
     cuesta —cada consulta que sale al proveedor se paga— y dice que empresa se
     esta quedando corta de plan.

     Las cifras del mes van en una linea dentro de la tarjeta, no en tarjetas
     aparte: la cabecera de la pantalla ya trae las suyas y dos filas seguidas
     de tarjetas se pisaban. --}}

<div class="space-y-5">

    {{-- Una sola tabla.

         Habia dos vistas conmutables —un total por empresa y el listado— y
         acababan diciendo lo mismo: con pocas empresas, el total no añade nada
         que no se lea ya en el listado. Quien mas busca cabe en la linea de
         resumen, y para eso no hace falta una tabla aparte. --}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Búsquedas desde el panel</h2>
                <p class="mt-0.5 text-xs text-gray-500">
                    15 por página. Para ver qué buscó una empresa cuando dice que no le salió.
                </p>
            </div>

            <div class="flex items-center gap-1 rounded-lg bg-gray-100 p-0.5 text-xs font-medium">
                <a href="{{ route('super-admin.consultas', ['tab' => 'interno']) }}"
                   class="rounded-md px-3 py-1.5 transition {{ $solo_fallos ? 'text-gray-600 hover:text-gray-900' : 'bg-white text-gray-900 shadow-sm' }}">
                    Todas
                </a>
                <a href="{{ route('super-admin.consultas', ['tab' => 'interno', 'fallos' => 1]) }}"
                   class="rounded-md px-3 py-1.5 transition {{ $solo_fallos ? 'bg-white text-red-700 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                    Con error
                    @if($fallos_internos_mes)
                        <span class="ml-1 rounded-full bg-red-100 px-1.5 py-0.5 text-red-700">{{ number_format($fallos_internos_mes) }}</span>
                    @endif
                </a>
            </div>
        </div>

        @include('super-admin.consultas.tabs._resumen_linea', [
            'r' => $resumen_interno,
            'que' => 'búsquedas',
            'lider' => $por_empresa->first(),
        ])


        <div>
            <table class="w-full table-auto divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="w-px whitespace-nowrap px-4 py-3">Fecha y hora</th>
                        <th class="w-[28%] px-4 py-3">Empresa</th>
                        <th class="w-[30%] px-4 py-3">Documento consultado</th>
                        <th class="w-[27%] px-4 py-3">Resultado</th>
                        <th class="w-px whitespace-nowrap px-4 py-3 text-right">Fuente / tiempo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($historial_interno as $h)
                        <tr class="{{ $h->exito ? '' : 'bg-red-50/40' }}">
                            <td class="whitespace-nowrap px-4 py-2.5 align-top text-gray-600">
                                <p class="font-medium text-gray-700">{{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d/m/Y') }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ \Illuminate\Support\Carbon::parse($h->created_at)->format('H:i:s') }}</p>
                            </td>
                            {{-- El consumo del mes va debajo del nombre, no en
                                 columna propia: es un dato de la empresa, no de
                                 esta consulta, y en medio partia la fila justo
                                 entre quien busco y que busco. --}}
                            <td class="px-4 py-2.5 align-top">
                                <div class="flex items-start gap-1.5">
                                    {{-- Sin empresa la hizo el super admin desde el
                                         panel: al dar de alta una llave se busca el
                                         RUC del cliente, y eso cuesta igual. Se dice
                                         quien fue, que con un guion no se distinguia
                                         de un dato que falta. --}}
                                    @if($h->empresa)
                                        <span class="break-words font-medium leading-5 text-gray-900">{{ $h->empresa }}</span>
                                    @else
                                        <span class="rounded bg-violet-50 px-1.5 py-0.5 text-xs font-medium text-violet-700">Super Admin</span>
                                    @endif
                                    @if($h->empresa)
                                        @php
                                            // Suspendida a mano cuenta como no activa: para
                                            // lo que se mira aqui, las dos significan que no
                                            // deberia estar operando.
                                            $activa = $h->empresa_activa && ! $h->suspendida_manualmente;
                                        @endphp
                                        @unless($activa)
                                            <span class="rounded-full bg-red-50 px-1.5 py-0.5 text-xs font-medium text-red-700"
                                                  title="{{ $h->suspendida_manualmente ? 'Suspendida manualmente' : 'Dada de baja' }}">
                                                Inactiva
                                            </span>
                                        @endunless
                                    @endif
                                </div>
                                @if($h->company_id)
                                    <p class="text-xs text-gray-400">
                                        {{ number_format($consumo_por_empresa[$h->company_id] ?? 0) }} este mes
                                    </p>
                                @endif
                            </td>
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
                                <p class="mt-1.5 break-words leading-5 text-slate-800">
                                    {{ $ficha['nombre'] ?? '—' }}
                                </p>
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
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500">
                                {{ $solo_fallos ? 'Ninguna búsqueda ha fallado.' : 'Todavía no hay ninguna búsqueda desde el panel.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($historial_interno->hasPages())
            <div class="border-t border-gray-100 px-5 py-3">
                {{ $historial_interno->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
</div>
