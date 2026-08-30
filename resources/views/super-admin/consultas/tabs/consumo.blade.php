{{-- Dos cosas, en este orden: cuanto gasta cada llave —que es lo que se cobra—
     y el registro de las ultimas consultas, que es a lo que se viene cuando un
     cliente dice "me falla". --}}
<div class="space-y-5">

    <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Quién consulta este mes</h2>
            <p class="mt-0.5 text-xs text-gray-500">Solo las que salieron bien: un número mal escrito no gasta cuota.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3">API Key</th>
                        <th class="px-5 py-3">Titular</th>
                        <th class="px-5 py-3">Plan</th>
                        <th class="px-5 py-3">Consultas</th>
                        <th class="px-5 py-3">Al proveedor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($consumo as $c)
                        <tr>
                            <td class="px-5 py-3">
                                <span class="font-medium text-gray-900">{{ $c->llave }}</span>
                                @if($c->entorno === 'sandbox')
                                    <span class="ml-1 rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">Sandbox</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $c->titular ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $c->plan ?? '—' }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ number_format($c->usadas) }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ number_format($c->al_proveedor) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-gray-500">Nadie ha consultado este mes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Últimas consultas</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Las 60 más recientes, con errores incluidos. Es lo que hay que mirar cuando algo falla.
            </p>
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
                        <th class="px-5 py-3">De dónde salió</th>
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
                                <span class="font-medium text-gray-900">{{ $h->llave ?? '—' }}</span>
                                @if($h->entorno === 'sandbox')
                                    <span class="ml-1 rounded-full bg-blue-50 px-1.5 py-0.5 text-xs font-medium text-blue-700">Sandbox</span>
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

                            {{-- De donde salio el dato: lo que dice si esa consulta
                                 costo dinero o se resolvio en casa. --}}
                            <td class="px-5 py-2.5">
                                <span class="rounded px-1.5 py-0.5 text-xs
                                    @if($h->fuente === 'proveedor') bg-amber-50 text-amber-700
                                    @elseif($h->fuente === 'padron') bg-emerald-50 text-emerald-700
                                    @elseif($h->fuente === 'consultado antes') bg-gray-100 text-gray-600
                                    @else bg-gray-50 text-gray-400 @endif">
                                    {{ $h->fuente }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-2.5 text-right text-gray-600">
                                {{ $h->ms !== null ? number_format($h->ms) . ' ms' : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-gray-500">Todavía no hay ninguna consulta.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</div>
