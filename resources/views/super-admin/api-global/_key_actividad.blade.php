@php
    /**
     * Traduce método + ruta a lo que la persona hizo de verdad.
     *
     * "POST api/facturas" no le dice nada a quien mira; "Emitió factura", sí.
     * Con eso una sola lista basta: se ve la secuencia de trabajo del dev y,
     * dentro de ella, dónde se atascó.
     */
    $recursos = [
        'facturas' => ['factura', 'facturas'],
        'boletas' => ['boleta', 'boletas'],
        'notas-credito' => ['nota de crédito', 'notas de crédito'],
        'notas-debito' => ['nota de débito', 'notas de débito'],
        'guias-remision' => ['guía de remisión', 'guías de remisión'],
        'resumenes' => ['resumen diario', 'resúmenes'],
        'clientes' => ['cliente', 'clientes'],
        'series' => ['serie', 'series'],
        'sucursales' => ['sucursal', 'sucursales'],
        'empresa' => ['empresa', 'datos de la empresa'],
        'anulaciones' => ['anulación', 'anulaciones'],
        'panel' => ['panel', 'el panel'],
        'catalogos' => ['catálogo', 'catálogos'],
        'ubigeos' => ['ubigeo', 'ubigeos'],
    ];

    $queHizo = function ($uso) use ($recursos) {
        $partes = array_values(array_filter(explode('/', str_replace('api/', '', $uso->path))));
        $recurso = $partes[0] ?? '';
        [$uno, $varios] = $recursos[$recurso] ?? [$recurso, $recurso];
        $ultimo = end($partes);

        if (str_starts_with((string) $ultimo, 'download-')) {
            return 'Descargó el ' . strtoupper(str_replace('download-', '', $ultimo)) . ' de una ' . $uno;
        }

        if ($uso->method === 'POST') {
            return $recurso === 'clientes' ? 'Registró cliente' : 'Emitió ' . $uno;
        }

        if (isset($partes[1]) && is_numeric($partes[1])) {
            return 'Consultó una ' . $uno;
        }

        return 'Consultó ' . $varios;
    };

    $motivos = [
        400 => 'petición mal formada',
        401 => 'credenciales rechazadas',
        403 => 'sin permiso',
        404 => 'no encontrado',
        422 => 'datos inválidos',
        429 => 'demasiadas peticiones',
        500 => 'error del servidor',
    ];

    /** Desde dónde llamó: sirve para saber si prueba a mano o ya integró. */
    $desde = function ($ua) {
        $ua = (string) $ua;
        return match (true) {
            str_contains($ua, 'PostmanRuntime') => 'Postman',
            str_contains($ua, 'insomnia') => 'Insomnia',
            str_contains($ua, 'curl') => 'cURL',
            str_contains($ua, 'GuzzleHttp') => 'PHP',
            str_contains($ua, 'python') => 'Python',
            str_contains($ua, 'axios'), str_contains($ua, 'node') => 'Node',
            str_contains($ua, 'okhttp') => 'Android',
            str_contains($ua, 'Mozilla') => 'Navegador',
            $ua === '' => '—',
            default => 'Otro',
        };
    };

    $ruta = route('super-admin.api-global.key-actividad', $apiKey);
@endphp

<div class="p-5">

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h4 class="truncate text-lg font-semibold text-gray-900">{{ $apiKey->name }}</h4>
            <p class="truncate text-sm text-gray-500">{{ $apiKey->company->razon_social ?? 'Sin empresa' }}</p>
        </div>
        <p class="text-sm text-gray-500">
            <span class="font-semibold text-gray-900">{{ number_format($totalUsage) }}</span> llamadas
            <span class="mx-1 text-gray-300">·</span>
            <span class="font-semibold {{ $totalErrores > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format($totalErrores) }}</span> con error
        </p>
    </div>

    {{-- Con miles de llamadas la lista completa no sirve de nada: lo que se
         busca casi siempre es qué falló. --}}
    <div class="mt-4 flex gap-2">
        <button type="button" onclick="window.openAdminModal('{{ $ruta }}', 'Actividad del token')"
                class="rounded-md px-3 py-1.5 text-xs font-medium {{ $soloErrores ? 'text-gray-600 hover:bg-gray-100' : 'bg-gray-900 text-white' }}">
            Todo
        </button>
        <button type="button" onclick="window.openAdminModal('{{ $ruta }}?solo=errores', 'Actividad del token')"
                class="rounded-md px-3 py-1.5 text-xs font-medium {{ $soloErrores ? 'bg-red-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
            Solo errores ({{ number_format($totalErrores) }})
        </button>
    </div>

    <div class="mt-3 overflow-hidden rounded-lg border border-gray-200">
        @forelse ($usos as $uso)
            @php $falla = $uso->status_code >= 400; @endphp
            <div class="flex items-center gap-3 border-b border-gray-100 px-3 py-2.5 last:border-b-0 {{ $falla ? 'bg-red-50/60' : '' }}">
                <span class="w-10 shrink-0 font-mono text-xs font-semibold {{ $uso->method === 'POST' ? 'text-violet-600' : 'text-sky-600' }}">{{ $uso->method }}</span>

                <span class="min-w-0 flex-1 truncate text-sm {{ $falla ? 'text-gray-900' : 'text-gray-700' }}">
                    {{ $queHizo($uso) }}
                    @if ($falla)
                        <span class="text-xs font-medium text-red-600">— {{ $motivos[$uso->status_code] ?? 'error' }}</span>
                    @endif
                </span>

                <span class="hidden w-20 shrink-0 truncate text-right text-xs text-gray-400 sm:block">{{ $desde($uso->user_agent) }}</span>
                <span class="shrink-0 font-mono text-xs font-semibold {{ $falla ? 'text-red-600' : 'text-emerald-600' }}">{{ $uso->status_code }}</span>
                <span class="w-14 shrink-0 text-right text-xs text-gray-400">{{ $uso->created_at->diffForHumans(short: true) }}</span>
            </div>
        @empty
            <p class="px-3 py-5 text-center text-sm text-gray-500">
                {{ $soloErrores ? 'Ningún error registrado.' : 'Todavía no ha usado la API.' }}
            </p>
        @endforelse
    </div>

    {{-- Paginación: los enlaces recargan el modal, no la página de detrás --}}
    @if ($usos->hasPages())
        <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
            <span>{{ $usos->firstItem() }}–{{ $usos->lastItem() }} de {{ number_format($usos->total()) }}</span>
            <div class="flex gap-2">
                @if ($usos->onFirstPage())
                    <span class="rounded-md px-3 py-1.5 text-gray-300">Anterior</span>
                @else
                    <button type="button" onclick="window.openAdminModal('{{ $usos->previousPageUrl() }}', 'Actividad del token')"
                            class="rounded-md px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-100">Anterior</button>
                @endif

                @if ($usos->hasMorePages())
                    <button type="button" onclick="window.openAdminModal('{{ $usos->nextPageUrl() }}', 'Actividad del token')"
                            class="rounded-md px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-100">Siguiente</button>
                @else
                    <span class="rounded-md px-3 py-1.5 text-gray-300">Siguiente</span>
                @endif
            </div>
        </div>
    @endif

</div>
