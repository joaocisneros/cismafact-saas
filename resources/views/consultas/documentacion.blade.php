@extends('layouts.consultas')

@section('title', 'Documentación')

@section('content')
@php
    $clave = $llave?->clave ?? 'tu_clave';
    $pista = $llave?->secreto_pista ?? 'tu_secreto';
    $base = url('/api/consultas');
@endphp

<div class="mb-5">
    <h1 class="text-xl font-semibold text-gray-900">Documentación</h1>
    <p class="text-sm text-gray-600">Copia y pega: tu clave ya va puesta en los ejemplos.</p>
</div>

<div class="mb-4 rounded-xl border border-gray-200 bg-white">
    <div class="border-b border-gray-100 px-4 py-3">
        <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">Cómo funciona</h2>
    </div>
    <ol class="space-y-3 p-4">
        <li class="flex gap-3 text-sm text-gray-700">
            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-blue-50 font-mono text-xs font-semibold text-blue-700">1</span>
            <span>
                <strong class="text-gray-900">Tu programa llama a una dirección</strong> con el número
                al final. Para el RUC 20555666777, la dirección acaba en <code class="rounded bg-gray-100 px-1 font-mono text-xs">/ruc/20555666777</code>.
            </span>
        </li>
        <li class="flex gap-3 text-sm text-gray-700">
            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-blue-50 font-mono text-xs font-semibold text-blue-700">2</span>
            <span>
                <strong class="text-gray-900">En esa llamada mandas tus dos credenciales.</strong>
                Van en las cabeceras y no en la dirección: así no quedan escritas en los registros del servidor.
            </span>
        </li>
        <li class="flex gap-3 text-sm text-gray-700">
            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-blue-50 font-mono text-xs font-semibold text-blue-700">3</span>
            <span>
                <strong class="text-gray-900">Recibes los datos en JSON</strong> y se te descuenta una
                consulta — salvo que el número no exista, que entonces no gasta nada.
            </span>
        </li>
    </ol>
</div>

<div class="grid gap-4 lg:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">Consultar un RUC</h2>
            <span class="text-xs text-gray-400">11 dígitos</span>
        </div>
        <div class="p-4">
            <p class="mb-2 text-xs text-gray-600">Lo que envías:</p>
<pre class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-3 font-mono text-xs leading-relaxed">curl {{ $base }}/ruc/20555666777 \
  -H "X-Api-Key: {{ $clave }}" \
  -H "X-Api-Secret: {{ $pista }}…"</pre>
            <p class="mb-2 mt-3 text-xs text-gray-600">Lo que te contesta:</p>
<pre class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-3 font-mono text-xs leading-relaxed">{
  "razon_social": "ACME CORPORATION SAC",
  "estado":       "ACTIVO",
  "condicion":    "HABIDO",
  "direccion":    "AV. LARCO 1234 - MIRAFLORES"
}</pre>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">Consultar un DNI</h2>
            <span class="text-xs text-gray-400">8 dígitos</span>
        </div>
        <div class="p-4">
            <p class="mb-2 text-xs text-gray-600">Lo que envías:</p>
<pre class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-3 font-mono text-xs leading-relaxed">curl {{ $base }}/dni/46756431 \
  -H "X-Api-Key: {{ $clave }}" \
  -H "X-Api-Secret: {{ $pista }}…"</pre>
            <p class="mb-2 mt-3 text-xs text-gray-600">Lo que te contesta:</p>
<pre class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-3 font-mono text-xs leading-relaxed">{
  "nombres":          "JUAN CARLOS",
  "apellido_paterno": "PEREZ",
  "apellido_materno": "LOPEZ"
}</pre>
        </div>
    </div>
</div>

<div class="mt-4 rounded-xl border border-gray-200 bg-white">
    <div class="border-b border-gray-100 px-4 py-3">
        <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">Si algo va mal</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                    <th class="px-4 py-2">Te responde</th>
                    <th class="px-4 py-2">Qué pasó</th>
                    <th class="px-4 py-2">Qué hacer</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                <tr class="border-b border-gray-50">
                    <td class="px-4 py-2 font-mono">401</td>
                    <td class="px-4 py-2">Tu clave o tu secreto no son correctos</td>
                    <td class="px-4 py-2">Cópialos otra vez desde <a href="{{ route('consultas.credenciales') }}" class="font-medium text-blue-700 hover:underline">Mi API</a></td>
                </tr>
                <tr class="border-b border-gray-50">
                    <td class="px-4 py-2 font-mono">404</td>
                    <td class="px-4 py-2">Ese número no existe en SUNAT o RENIEC</td>
                    <td class="px-4 py-2">No gasta cuota. Comprueba el número</td>
                </tr>
                <tr class="border-b border-gray-50">
                    <td class="px-4 py-2 font-mono">422</td>
                    <td class="px-4 py-2">El número no tiene los dígitos que toca</td>
                    <td class="px-4 py-2">RUC son 11 y DNI son 8</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono">429</td>
                    <td class="px-4 py-2">Te quedaste sin cuota este mes</td>
                    <td class="px-4 py-2">Espera al día 1 o escríbenos para ampliar el plan</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 grid gap-4 lg:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">Desde PHP</h2>
        </div>
        <div class="p-4">
<pre class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-3 font-mono text-xs leading-relaxed">$r = Http::withHeaders([
    'X-Api-Key'    => '{{ \Illuminate\Support\Str::limit($clave, 22, '…') }}',
    'X-Api-Secret' => '{{ $pista }}…',
])->get('{{ $base }}/ruc/20555666777');

$empresa = $r->json();</pre>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">Cuánta cuota te queda</h2>
        </div>
        <div class="p-4">
            <p class="mb-2 text-xs text-gray-600">Lo mismo que ves en Consumo, para que tu programa lo consulte solo:</p>
<pre class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-3 font-mono text-xs leading-relaxed">curl {{ $base }}/cuota \
  -H "X-Api-Key: {{ $clave }}" \
  -H "X-Api-Secret: {{ $pista }}…"</pre>
            <p class="mt-3 text-xs text-gray-600">Esta llamada <strong class="text-gray-900">no gasta cuota</strong>.</p>
        </div>
    </div>
</div>
@endsection
