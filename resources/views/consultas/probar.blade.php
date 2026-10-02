@extends('layouts.consultas')

@section('title', 'Probar API')

@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-semibold text-gray-900">Probar API</h1>
    <p class="text-[15px] text-gray-600">Realiza una consulta desde el panel y comprueba la respuesta de tu integración.</p>
</div>

@if($llaves->isEmpty())
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
        <p class="font-semibold text-amber-700">No tienes ninguna llave disponible para probar.</p>
    </div>
@else
    @php
        $seleccionada = $llaves->firstWhere('id', (int) old('llave_id')) ?? $llaves->first();
        $servicios = $seleccionada?->servicios ?? [];
        $serviciosDisponibles = $llaves->pluck('servicios')->flatten()->unique();
    @endphp
    <div class="grid gap-5 xl:grid-cols-[.75fr_1.25fr]">
        <form method="POST" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            @csrf
            <div class="border-b border-gray-100 bg-blue-50 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Nueva consulta</h2>
                <p class="mt-1 text-sm text-gray-500">La prueba quedará registrada en tu historial.</p>
            </div>
            <div class="space-y-5 p-5">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-semibold text-gray-700">Credencial</span>
                    <select name="llave_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                        @foreach($llaves as $llave)
                            <option value="{{ $llave->id }}" @selected((int) old('llave_id', $seleccionada?->id) === $llave->id)>
                                {{ $llave->entorno === 'sandbox' ? 'Sandbox' : 'Producción' }} · {{ collect($llave->servicios)->map(fn($s) => strtoupper($s))->join(' y ') }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <div>
                    <span class="mb-1.5 block text-sm font-semibold text-gray-700">Tipo de consulta</span>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach(['ruc' => ['RUC', '11 dígitos'], 'dni' => ['DNI', '8 dígitos']] as $valor => [$nombre, $detalle])
                            @continue(!$serviciosDisponibles->contains($valor))
                            <label class="cursor-pointer rounded-lg border border-gray-200 p-3 transition hover:bg-gray-50">
                                <input type="radio" name="tipo" value="{{ $valor }}" class="text-blue-600" @checked(old('tipo', $servicios[0] ?? 'ruc') === $valor)>
                                <span class="ml-1 font-semibold text-gray-900">{{ $nombre }}</span>
                                <span class="mt-1 block text-xs text-gray-500">{{ $detalle }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-semibold text-gray-700">Número a consultar</span>
                    <input name="numero" value="{{ old('numero') }}" inputmode="numeric" maxlength="11" required placeholder="Escribe el RUC o DNI"
                           class="w-full rounded-lg border border-gray-300 px-3 py-3 font-mono text-base outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                </label>

                @if($errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</div>
                @endif

                <button class="w-full rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white transition hover:bg-blue-700">Consultar ahora</button>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div><h2 class="font-semibold text-gray-900">Respuesta de la API</h2><p class="mt-1 text-sm text-gray-500">El mismo JSON que recibirá tu aplicación.</p></div>
                @if($estadoHttp)
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $estadoHttp < 300 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">HTTP {{ $estadoHttp }}</span>
                @endif
            </div>
            <div class="p-5">
                @if($resultado)
                    <pre class="min-h-72 overflow-auto rounded-xl border border-gray-200 bg-gray-50 p-5 font-mono text-sm leading-6 text-gray-700">{{ json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                @else
                    <div class="flex min-h-72 flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center">
                        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-xl text-blue-700">{ }</div>
                        <p class="font-semibold text-gray-700">La respuesta aparecerá aquí</p>
                        <p class="mt-1 max-w-sm text-sm text-gray-500">Selecciona una credencial, elige RUC o DNI y realiza la consulta.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
@endsection
