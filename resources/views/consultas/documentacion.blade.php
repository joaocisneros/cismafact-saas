@extends('layouts.consultas')

@section('title', 'Documentación')

@section('content')
@php
    $clave = $llave?->clave ?? 'tu_clave';
    $pista = ($llave?->secreto_pista ?? 'tu_secreto') . '…';
    $base = url('/api/consultas');

    /*
     * Los ejemplos, armados aquí y no repetidos por la pantalla.
     *
     * Antes había seis bloques de código sueltos, todos con el mismo peso: no
     * se veía qué mirar primero ni qué diferenciaba uno de otro. Ahora se
     * elige qué consultar y en qué lenguaje, y se enseña solo ese.
     */
    $ejemplos = [
        'ruc' => [
            'titulo' => 'Consultar un RUC',
            'pie' => '11 dígitos',
            'curl' => "curl {$base}/ruc/20555666777 \\\n  -H \"X-Api-Key: {$clave}\" \\\n  -H \"X-Api-Secret: {$pista}\"",
            'php' => "\$r = Http::withHeaders([\n    'X-Api-Key'    => '{$clave}',\n    'X-Api-Secret' => '{$pista}',\n])->get('{$base}/ruc/20555666777');\n\n\$empresa = \$r->json();",
            'js' => "const r = await fetch('{$base}/ruc/20555666777', {\n  headers: {\n    'X-Api-Key': '{$clave}',\n    'X-Api-Secret': '{$pista}',\n  },\n});\n\nconst empresa = await r.json();",
            'respuesta' => "{\n  \"razon_social\": \"ACME CORPORATION SAC\",\n  \"estado\":       \"ACTIVO\",\n  \"condicion\":    \"HABIDO\",\n  \"direccion\":    \"AV. LARCO 1234 - MIRAFLORES\"\n}",
        ],
        'dni' => [
            'titulo' => 'Consultar un DNI',
            'pie' => '8 dígitos',
            'curl' => "curl {$base}/dni/46756431 \\\n  -H \"X-Api-Key: {$clave}\" \\\n  -H \"X-Api-Secret: {$pista}\"",
            'php' => "\$r = Http::withHeaders([\n    'X-Api-Key'    => '{$clave}',\n    'X-Api-Secret' => '{$pista}',\n])->get('{$base}/dni/46756431');\n\n\$persona = \$r->json();",
            'js' => "const r = await fetch('{$base}/dni/46756431', {\n  headers: {\n    'X-Api-Key': '{$clave}',\n    'X-Api-Secret': '{$pista}',\n  },\n});\n\nconst persona = await r.json();",
            'respuesta' => "{\n  \"nombres\":          \"JUAN CARLOS\",\n  \"apellido_paterno\": \"PEREZ\",\n  \"apellido_materno\": \"LOPEZ\"\n}",
        ],
        'cuota' => [
            'titulo' => 'Ver tu cuota',
            'pie' => 'esta no gasta',
            'curl' => "curl {$base}/cuota \\\n  -H \"X-Api-Key: {$clave}\" \\\n  -H \"X-Api-Secret: {$pista}\"",
            'php' => "\$r = Http::withHeaders([\n    'X-Api-Key'    => '{$clave}',\n    'X-Api-Secret' => '{$pista}',\n])->get('{$base}/cuota');\n\n\$cuota = \$r->json();",
            'js' => "const r = await fetch('{$base}/cuota', {\n  headers: {\n    'X-Api-Key': '{$clave}',\n    'X-Api-Secret': '{$pista}',\n  },\n});\n\nconst cuota = await r.json();",
            'respuesta' => "{\n  \"servicios\": [\n    { \"servicio\": \"ruc\", \"limite_mensual\": 1000, \"usadas\": 2, \"restantes\": 998 },\n    { \"servicio\": \"dni\", \"limite_mensual\": 300,  \"usadas\": 8, \"restantes\": 292 }\n  ]\n}",
        ],
    ];
@endphp

<div class="mb-5">
    <h1 class="text-2xl font-semibold text-gray-900">Documentación</h1>
    <p class="text-[15px] text-gray-600">Elige qué consultar y en qué lenguaje. Tu clave ya va puesta.</p>
</div>

<div x-data="{ que: 'ruc', lenguaje: 'curl', ejemplos: @js($ejemplos) }" class="space-y-4">

    {{-- Lo que hace falta para cualquier llamada, antes que los ejemplos: sin
         estas dos cosas no funciona ninguno. --}}
    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-3.5">
            <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400">Lo que va en toda llamada</h2>
        </div>
        <div class="grid gap-4 p-5 md:grid-cols-2">
            <div>
                <p class="mb-1.5 text-sm font-semibold text-gray-600">Cabecera <code class="font-mono">X-Api-Key</code></p>
                <div class="flex gap-2">
                    <code class="flex-1 overflow-x-auto whitespace-nowrap rounded-md border border-gray-200 bg-gray-50 px-3.5 py-2.5 font-mono text-sm">{{ $clave }}</code>
                    <button type="button"
                            @click="navigator.clipboard.writeText(@js($clave)); $el.textContent = 'Copiada'; setTimeout(() => $el.textContent = 'Copiar', 1400)"
                            class="shrink-0 rounded-md border border-gray-300 px-3.5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Copiar
                    </button>
                </div>
            </div>
            <div>
                <p class="mb-1.5 text-sm font-semibold text-gray-600">Cabecera <code class="font-mono">X-Api-Secret</code></p>
                <div class="flex gap-2">
                    <code class="flex-1 overflow-x-auto whitespace-nowrap rounded-md border border-gray-200 bg-gray-50 px-3.5 py-2.5 font-mono text-sm text-gray-500">{{ $pista }} — el tuyo está en Mi API</code>
                    <a href="{{ route('consultas.credenciales') }}"
                       class="shrink-0 rounded-md border border-gray-300 px-3.5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Verlo
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Los ejemplos: se elige qué consultar y en qué lenguaje. --}}
    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-4 py-3">
            <div class="flex flex-wrap gap-1">
                @foreach($ejemplos as $id => $ejemplo)
                    <button type="button" @click="que = @js($id)"
                            :class="que === @js($id) ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50'"
                            class="rounded-lg px-3.5 py-2 text-sm font-semibold">
                        {{ $ejemplo['titulo'] }}
                    </button>
                @endforeach
            </div>

            <div class="flex gap-1 rounded-lg bg-gray-100 p-0.5">
                @foreach(['curl' => 'curl', 'php' => 'PHP', 'js' => 'JavaScript'] as $id => $nombre)
                    <button type="button" @click="lenguaje = @js($id)"
                            :class="lenguaje === @js($id) ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800'"
                            class="rounded-md px-3 py-1.5 text-sm font-medium">
                        {{ $nombre }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="grid gap-5 p-5 lg:grid-cols-2">
            <div>
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-gray-600">
                        Lo que envías
                        <span class="font-normal text-gray-400" x-text="'· ' + ejemplos[que].pie"></span>
                    </p>
                    <button type="button"
                            @click="navigator.clipboard.writeText(ejemplos[que][lenguaje]); $el.textContent = 'Copiado'; setTimeout(() => $el.textContent = 'Copiar', 1400)"
                            class="shrink-0 rounded-md border border-gray-300 px-2.5 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                        Copiar
                    </button>
                </div>
                <pre class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-4 font-mono text-sm leading-relaxed" x-text="ejemplos[que][lenguaje]"></pre>
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-gray-600">Lo que te contesta</p>
                <pre class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-4 font-mono text-sm leading-relaxed" x-text="ejemplos[que].respuesta"></pre>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-3.5">
            <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400">Si algo va mal</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-[15px]">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3">Te responde</th>
                        <th class="px-5 py-3">Qué pasó</th>
                        <th class="px-5 py-3">Qué hacer</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    <tr class="border-b border-gray-50">
                        <td class="px-5 py-3 font-mono font-semibold text-red-700">401</td>
                        <td class="px-5 py-3">Tu clave o tu secreto no son correctos</td>
                        <td class="px-5 py-3">Cópialos otra vez desde <a href="{{ route('consultas.credenciales') }}" class="font-medium text-blue-700 hover:underline">Mi API</a></td>
                    </tr>
                    <tr class="border-b border-gray-50">
                        <td class="px-5 py-3 font-mono font-semibold text-gray-500">404</td>
                        <td class="px-5 py-3">Ese número no existe en SUNAT o RENIEC</td>
                        <td class="px-5 py-3"><strong class="text-gray-900">No gasta cuota.</strong> Comprueba el número</td>
                    </tr>
                    <tr class="border-b border-gray-50">
                        <td class="px-5 py-3 font-mono font-semibold text-amber-700">422</td>
                        <td class="px-5 py-3">El número no tiene los dígitos que toca</td>
                        <td class="px-5 py-3">RUC son 11 y DNI son 8</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-3 font-mono font-semibold text-amber-700">429</td>
                        <td class="px-5 py-3">Te quedaste sin cuota este mes</td>
                        <td class="px-5 py-3">Espera al día 1 o escríbenos para ampliar el plan</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl bg-blue-50 px-5 py-4 text-[15px] text-gray-700">
        <strong class="text-gray-900">Las credenciales van en las cabeceras, no en la dirección.</strong>
        Si las pones dentro de la URL quedan escritas en los registros de cualquier servidor por el que pase la llamada.
    </div>
</div>
@endsection
