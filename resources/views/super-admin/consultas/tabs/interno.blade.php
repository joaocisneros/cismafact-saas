{{-- Lo que gastan las empresas de casa buscando un RUC o un DNI desde el panel.

     Aparte del «Consumo externo» a proposito: aquello es lo que consumen los
     clientes que pagan por consultar, y descuenta cuota. Esto no se cobra, pero
     cuesta —cada consulta que sale al proveedor se paga— y dice que empresa se
     esta quedando corta de plan.

     Las cifras del mes van en una linea dentro de la tarjeta, no en tarjetas
     aparte: la cabecera de la pantalla ya trae las suyas y dos filas seguidas
     de tarjetas se pisaban. --}}

<div class="space-y-5">

    {{-- Las dos vistas del mismo dato, en una sola tarjeta con dos botones.

         Lado a lado se quedaban las dos estrechas y ninguna se leia bien; una
         debajo de otra obligaba a bajar. Se miran de una en una, asi que se
         conmutan. --}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
             x-data="{ vista: 'empresas' }">

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <div class="flex items-center gap-1 rounded-lg bg-gray-100 p-0.5 text-xs font-medium">
                <button type="button" @click="vista = 'empresas'"
                        :class="vista === 'empresas' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                        class="rounded-md px-3 py-1.5 transition">
                    Por empresa
                </button>
                <button type="button" @click="vista = 'busquedas'"
                        :class="vista === 'busquedas' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                        class="rounded-md px-3 py-1.5 transition">
                    Últimas búsquedas
                    @if($fallos_internos_mes)
                        <span class="ml-1 rounded-full bg-red-100 px-1.5 py-0.5 text-red-700">{{ number_format($fallos_internos_mes) }}</span>
                    @endif
                </button>
            </div>

            {{-- El filtro es solo del listado, asi que solo sale con el. --}}
            <div x-show="vista === 'busquedas'" x-cloak
                 class="flex items-center gap-1 rounded-lg bg-gray-100 p-0.5 text-xs font-medium">
                <a href="{{ route('super-admin.consultas', ['tab' => 'interno']) }}"
                   class="rounded-md px-3 py-1.5 transition {{ $solo_fallos ? 'text-gray-600 hover:text-gray-900' : 'bg-white text-gray-900 shadow-sm' }}">
                    Todas
                </a>
                <a href="{{ route('super-admin.consultas', ['tab' => 'interno', 'fallos' => 1]) }}"
                   class="rounded-md px-3 py-1.5 transition {{ $solo_fallos ? 'bg-white text-red-700 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                    Con error
                </a>
            </div>
        </div>

        <div x-show="vista === 'empresas'">
        @include('super-admin.consultas.tabs._resumen_linea', ['r' => $resumen_interno, 'que' => 'búsquedas'])
        <p class="border-b border-gray-100 px-5 py-2.5 text-xs text-gray-500">
            De más a menos. Quien salga mucho al proveedor es a quien se le está quedando corto el plan.
        </p>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Empresa</th>
                        <th class="px-5 py-3 text-right">Consultas</th>
                        <th class="px-5 py-3 text-right">Costaron</th>
                        <th class="px-5 py-3 text-right">Gratis</th>
                        <th class="px-5 py-3 text-right">Fallidas</th>
                        <th class="px-5 py-3">Última</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($por_empresa as $e)
                        <tr>
                            <td class="max-w-0 px-5 py-2.5">
                                {{-- truncate: en media pantalla el nombre largo se
                                     partia en tres lineas y descuadraba la fila. --}}
                                <p class="truncate font-medium text-gray-900" title="{{ $e->empresa ?? 'Empresa eliminada' }}">
                                    {{ $e->empresa ?? 'Empresa eliminada' }}
                                </p>
                                @if($e->ruc)
                                    <p class="font-mono text-xs text-gray-400">{{ $e->ruc }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-right font-medium text-gray-900">{{ number_format($e->total) }}</td>
                            <td class="px-5 py-2.5 text-right">
                                @if($e->proveedor)
                                    <span class="rounded bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-700">{{ number_format($e->proveedor) }}</span>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-right">
                                @if($e->en_casa)
                                    <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-xs font-medium text-emerald-700">{{ number_format($e->en_casa) }}</span>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-right">
                                @if($e->fallidas)
                                    <span class="rounded bg-red-50 px-1.5 py-0.5 text-xs font-medium text-red-700">{{ number_format($e->fallidas) }}</span>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-2.5 text-xs text-gray-500">
                                {{ \Illuminate\Support\Carbon::parse($e->ultima)->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500">
                                Este mes nadie ha buscado un RUC ni un DNI desde el panel.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>

        <div x-show="vista === 'busquedas'" x-cloak>
        <p class="border-b border-gray-100 px-5 py-2.5 text-xs text-gray-500">
            Las 40 más recientes. Para ver qué buscó una empresa cuando dice que no le salió.
        </p>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    {{-- Cinco columnas, no siete: al ir a media pantalla, el
                         servicio cabe pegado al numero y lo que tardo va en el
                         titulo de la fecha, que casi nunca se mira. --}}
                    <tr>
                        <th class="px-4 py-3">Cuándo</th>
                        <th class="px-4 py-3">Empresa</th>
                        <th class="px-4 py-3">Consultó</th>
                        <th class="px-4 py-3">Resultado</th>
                        <th class="px-4 py-3">¿Costó?</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($historial_interno as $h)
                        <tr class="{{ $h->exito ? '' : 'bg-red-50/40' }}">
                            <td class="whitespace-nowrap px-4 py-2.5 text-xs text-gray-600"
                                title="{{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d/m/Y H:i:s') }}{{ $h->ms !== null ? ' · tardó ' . number_format($h->ms) . ' ms' : '' }}">
                                {{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d/m H:i') }}
                            </td>
                            <td class="max-w-0 px-4 py-2.5">
                                <p class="truncate text-gray-900" title="{{ $h->empresa ?? '—' }}">{{ $h->empresa ?? '—' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5">
                                <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600">{{ strtoupper($h->tipo) }}</span>
                                <span class="ml-1 font-mono text-xs text-gray-700">{{ $h->numero }}</span>
                            </td>
                            <td class="px-4 py-2.5">
                                @if($h->exito)
                                    <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Éxito</span>
                                @else
                                    <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Sin ficha</span>
                                    @if($h->motivo)
                                        <p class="mt-0.5 max-w-xs truncate text-xs text-red-600" title="{{ $h->motivo }}">{{ $h->motivo }}</p>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-2.5">
                                @include('super-admin.consultas.tabs._fuente', ['fuente' => $h->fuente])
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
        </div>
    </section>
</div>
