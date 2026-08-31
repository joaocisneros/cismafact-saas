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
                    Las 40 más recientes. Para ver qué buscó una empresa cuando dice que no le salió.
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


        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="w-px whitespace-nowrap px-4 py-3">Fecha y hora</th>
                        <th class="whitespace-nowrap px-4 py-3">Empresa</th>
                        <th class="w-px whitespace-nowrap px-4 py-3">Servicio</th>
                        <th class="w-px whitespace-nowrap px-4 py-3">Número</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="w-px whitespace-nowrap px-4 py-3">Origen</th>
                        <th class="w-px whitespace-nowrap px-4 py-3 text-right">Tardó</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($historial_interno as $h)
                        <tr class="{{ $h->exito ? '' : 'bg-red-50/40' }}">
                            <td class="whitespace-nowrap px-4 py-2.5 text-gray-600">
                                {{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d/m/Y H:i:s') }}
                            </td>
                            {{-- El consumo del mes va debajo del nombre, no en
                                 columna propia: es un dato de la empresa, no de
                                 esta consulta, y en medio partia la fila justo
                                 entre quien busco y que busco. --}}
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-gray-900">{{ $h->empresa ?? '—' }}</span>
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
                            <td class="whitespace-nowrap px-4 py-2.5 text-gray-600">{{ strtoupper($h->tipo) }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs text-gray-700">{{ $h->numero }}</td>
                            <td class="px-4 py-2.5">
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
                            <td class="px-4 py-2.5">
                                @include('super-admin.consultas.tabs._fuente', ['fuente' => $h->fuente, 'coste' => false])
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right text-gray-600">
                                {{ $h->ms !== null ? number_format($h->ms) . ' ms' : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-500">
                                {{ $solo_fallos ? 'Ninguna búsqueda ha fallado.' : 'Todavía no hay ninguna búsqueda desde el panel.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
