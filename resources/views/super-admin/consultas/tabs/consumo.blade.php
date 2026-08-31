{{-- El registro de consultas. Lo que se mira cuando un cliente dice "me falla".

     Aqui NO va cuanto gasta cada llave: eso esta en «Mis APIs», pegado a su
     llave y con su tope al lado, que es donde uno mira para saber si a alguien
     le queda cuota. Estaba en los dos sitios y era la misma cuenta dos veces. --}}
@php
    $r = $resumen_externo;
    // Lo unico que cuesta dinero es lo que salio al proveedor.
    $ahorro = $r['total'] ? round($r['en_casa'] / $r['total'] * 100) : 0;
@endphp

<div class="space-y-5">

{{-- El mes de un vistazo, igual que en la pestaña de dentro: son las mismas
     cuatro cifras y se comparan de un lado a otro sin traducir nada. --}}
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-medium text-gray-500">Consultas este mes</p>
        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($r['total']) }}</p>
        <p class="mt-0.5 text-xs text-gray-400">De clientes con llave</p>
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
            {{ $r['ms_medio'] ? number_format($r['ms_medio']) . ' ms de media' : 'Un número mal escrito no gasta cuota' }}
        </p>
    </div>
</div>

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

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-5 py-3">Fecha y hora</th>
                    <th class="px-5 py-3">API Key</th>
                    <th class="px-5 py-3">Servicio</th>
                    <th class="px-5 py-3">Número</th>
                    <th class="px-5 py-3">Resultado</th>
                    <th class="px-5 py-3">¿Costó?</th>
                    <th class="px-5 py-3 text-right">Tardó</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($historial as $h)
                    <tr class="{{ $h->exito ? '' : 'bg-red-50/40' }}">
                        <td class="whitespace-nowrap px-5 py-2.5 text-gray-600">
                            {{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d/m/Y H:i:s') }}
                        </td>

                        <td class="px-5 py-2.5">
                            @if($h->llave)
                                <span class="font-medium text-gray-900">{{ $h->llave }}</span>
                                @if($h->entorno === 'sandbox')
                                    <span class="ml-1 rounded-full bg-blue-50 px-1.5 py-0.5 text-xs font-medium text-blue-700">Sandbox</span>
                                @endif
                            @else
                                {{-- La llave ya no existe, pero lo consultado sigue contando. --}}
                                <span class="text-xs italic text-gray-400">llave eliminada</span>
                            @endif
                        </td>

                        <td class="px-5 py-2.5 text-gray-600">{{ strtoupper($h->tipo) }}</td>

                        <td class="px-5 py-2.5 font-mono text-xs text-gray-700">{{ $h->numero }}</td>

                        <td class="px-5 py-2.5">
                            @if($h->exito)
                                <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Éxito</span>
                            @else
                                <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Error</span>
                                @if($h->motivo)
                                    <p class="mt-0.5 max-w-xs truncate text-xs text-red-600" title="{{ $h->motivo }}">{{ $h->motivo }}</p>
                                @endif
                            @endif
                        </td>

                        {{-- De donde salio el dato: es lo que dice si esa consulta
                             costo dinero o se resolvio en casa. --}}
                        <td class="px-5 py-2.5">
                            @include('super-admin.consultas.tabs._fuente', ['fuente' => $h->fuente])
                        </td>

                        <td class="whitespace-nowrap px-5 py-2.5 text-right text-gray-600">
                            {{ $h->ms !== null ? number_format($h->ms) . ' ms' : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-gray-500">
                            {{ $solo_fallos ? 'Ninguna consulta ha fallado.' : 'Todavía no hay ninguna consulta.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
</div>
