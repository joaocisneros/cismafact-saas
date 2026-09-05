{{--
    Las consultas hechas, con lo que gastó cada una.

    La columna de cuota está porque es la pregunta que más se hace al cuadrar
    el gasto: una consulta a un número que no existe no descuenta nada, y sin
    decirlo la cuenta nunca sale.
--}}
@props(['filas', 'conCuota' => false])

<div class="overflow-x-auto">
    <table class="min-w-full text-[15px]">
        <thead>
            <tr class="border-b border-gray-100 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                <th class="px-5 py-3">Fecha</th>
                <th class="px-5 py-3">Tipo</th>
                <th class="px-5 py-3">Número</th>
                <th class="px-5 py-3">Resultado</th>
                @if($conCuota)<th class="px-5 py-3 text-right">Cuota</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse($filas as $fila)
                @php($gasta = $fila->exito && $fila->fuente !== 'modo prueba')
                <tr class="border-b border-gray-50 last:border-0">
                    <td class="px-5 py-3 tabular-nums text-gray-700">
                        {{ \Illuminate\Support\Carbon::parse($fila->created_at)->format('d/m H:i') }}
                    </td>
                    <td class="px-5 py-3 font-mono text-sm uppercase text-gray-500">{{ $fila->tipo }}</td>
                    <td class="px-5 py-3 tabular-nums text-gray-900">{{ $fila->numero }}</td>
                    <td class="px-5 py-3">
                        @if($fila->exito)
                            <span class="text-sm font-medium text-emerald-700">Encontrado</span>
                        @else
                            <span class="text-sm text-gray-400">Sin ficha</span>
                        @endif
                    </td>
                    @if($conCuota)
                        <td class="px-5 py-3 text-right tabular-nums {{ $gasta ? 'text-gray-700' : 'text-gray-400' }}">
                            {{ $gasta ? '−1' : '0' }}
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $conCuota ? 5 : 4 }}" class="px-4 py-8 text-center text-sm text-gray-500">
                        Todavía no has hecho ninguna consulta.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
