{{-- Lo que gastan las empresas de casa buscando un RUC o un DNI desde el panel.

     Aparte del «Consumo externo» a proposito: aquello es lo que consumen los
     clientes que pagan por consultar, y descuenta cuota. Esto no se cobra, pero
     cuesta —cada consulta que sale al proveedor se paga— y dice que empresa se
     esta quedando corta de plan.

     El orden va de lo general a lo concreto: primero cuanto y cuanto costo,
     luego quien, y al final consulta por consulta. --}}
@php
    $r = $resumen_interno;
    // Lo unico que cuesta dinero es lo que salio al proveedor: el resto se
    // resolvio con el padron o con algo ya consultado antes.
    $ahorro = $r['total'] ? round($r['en_casa'] / $r['total'] * 100) : 0;
@endphp

<div class="space-y-5">

    {{-- 1. El mes de un vistazo --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500">Consultas este mes</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($r['total']) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">Desde el panel, sin cobrar a nadie</p>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-sm">
            <p class="text-xs font-medium text-amber-800">Costaron dinero</p>
            <p class="mt-1 text-2xl font-semibold text-amber-900">{{ number_format($r['proveedor']) }}</p>
            <p class="mt-0.5 text-xs text-amber-700">Hubo que salir al proveedor</p>
        </div>

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 shadow-sm">
            <p class="text-xs font-medium text-emerald-800">Salieron gratis</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ number_format($r['en_casa']) }}</p>
            <p class="mt-0.5 text-xs text-emerald-700">{{ $ahorro }}% del total, ya las teníamos</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500">Fallidas</p>
            <p class="mt-1 text-2xl font-semibold {{ $r['fallidas'] ? 'text-red-700' : 'text-gray-900' }}">
                {{ number_format($r['fallidas']) }}
            </p>
            <p class="mt-0.5 text-xs text-gray-400">
                {{ $r['ms_medio'] ? number_format($r['ms_medio']) . ' ms de media' : 'Un número mal escrito no cuesta' }}
            </p>
        </div>
    </div>

    {{-- 2 y 3, lado a lado: quien gasta y que se busco.

         Se miran juntas —«esta empresa gasta mucho: ¿que esta buscando?»— y
         apiladas obligaban a bajar y subir para cruzar las dos. En pantalla
         estrecha vuelven a apilarse solas. --}}
    <div class="grid gap-5 xl:grid-cols-5 xl:items-start">

    {{-- Quien tira del servicio --}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm xl:col-span-2">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Por empresa, este mes</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                De más a menos. Quien salga mucho al proveedor es a quien se le está quedando corto el plan.
            </p>
        </div>

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
                            <td class="px-5 py-2.5">
                                <span class="font-medium text-gray-900">{{ $e->empresa ?? 'Empresa eliminada' }}</span>
                                @if($e->ruc)
                                    <span class="ml-1 font-mono text-xs text-gray-400">{{ $e->ruc }}</span>
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
    </section>

    {{-- Consulta por consulta, para cuando hay que mirar un caso --}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm xl:col-span-3">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Últimas búsquedas</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Las 40 más recientes. Sirve para ver qué buscó una empresa cuando dice que no le salió.
            </p>
        </div>

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
                            <td class="px-4 py-2.5 text-gray-900">{{ $h->empresa ?? '—' }}</td>
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
                                Todavía no hay ninguna búsqueda desde el panel.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    </div>
</div>
