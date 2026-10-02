{{--
    Las consultas hechas, con lo que gastó cada una.

    La columna de cuota está porque es la pregunta que más se hace al cuadrar
    el gasto: una consulta a un número que no existe no descuenta nada, y sin
    decirlo la cuenta nunca sale.
--}}
@props(['filas', 'conCuota' => false])

<div class="overflow-x-auto">
    <table class="min-w-full text-[15px]">
        <thead class="bg-gray-50">
            <tr class="border-b border-gray-100 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                <th class="px-5 py-3">Fecha</th>
                <th class="px-5 py-3">Tipo</th>
                <th class="px-5 py-3">Entorno</th>
                <th class="px-5 py-3">Número</th>
                <th class="px-5 py-3">Resultado</th>
                @if($conCuota)<th class="px-5 py-3 text-right">Cuota</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse($filas as $fila)
                @php($gasta = $fila->exito && $fila->fuente !== 'modo prueba')
                <tr class="border-b border-gray-100 transition-colors last:border-0 hover:bg-gray-50">
                    <td class="px-5 py-3 tabular-nums text-gray-700">
                        {{ \Illuminate\Support\Carbon::parse($fila->created_at)->format('d/m H:i') }}
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex min-w-12 justify-center rounded-full px-2.5 py-1 font-mono text-xs font-semibold uppercase {{ strtolower($fila->tipo) === 'ruc' ? 'bg-blue-50 text-blue-700' : 'bg-violet-50 text-violet-700' }}">
                            {{ $fila->tipo }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        @if(($fila->entorno ?? 'produccion') === 'sandbox')
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700"><span class="h-1.5 w-1.5 rounded-full bg-violet-500"></span>Sandbox</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700"><span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>Producción</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 tabular-nums text-gray-900">{{ $fila->numero }}</td>
                    <td class="px-5 py-3">
                        @if($fila->exito)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Encontrado</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500"><span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>Sin ficha</span>
                        @endif
                    </td>
                    @if($conCuota)
                        <td class="px-5 py-3 text-right tabular-nums">
                            <span class="inline-flex min-w-16 justify-center rounded-full px-2 py-1 text-xs font-semibold {{ $gasta ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $gasta ? '−1 consulta' : 'Sin costo' }}
                            </span>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $conCuota ? 6 : 5 }}" class="px-4 py-8 text-center text-sm text-gray-500">
                        Todavía no has hecho ninguna consulta.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
