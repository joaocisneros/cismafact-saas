@extends('layouts.app')

@section('title', 'API RUC y DNI')

@section('content')
<div class="space-y-6">

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    {{-- Tres cifras que cuentan de un vistazo de donde salen los datos hoy. --}}
    <section class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">Guardadas de consultas anteriores</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($cache['total']) }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ number_format($cache['ruc']) }} RUC · {{ number_format($cache['dni']) }} DNI</p>
        </div>

        <a href="{{ route('super-admin.padron') }}"
           class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-gray-300 hover:shadow-md">
            <p class="text-xs text-gray-500">Padrón local de SUNAT</p>
            <p class="mt-1 text-2xl font-semibold {{ $padron['filas'] ? 'text-gray-900' : 'text-gray-400' }}">
                {{ number_format($padron['filas']) }}
            </p>
            <p class="mt-1 text-xs text-gray-500">
                {{ $padron['filas'] ? 'RUC en la copia local' : 'Sin importar todavía' }}
            </p>
        </a>

        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">Proveedor externo</p>
            <p class="mt-1 text-sm font-semibold {{ !empty($ajustes['consultas_url']) ? 'text-green-700' : 'text-amber-700' }}">
                {{ !empty($ajustes['consultas_url']) ? '● Configurado' : '○ Sin configurar' }}
            </p>
            <p class="mt-1 truncate text-xs text-gray-500">
                {{ $ajustes['consultas_url'] ?? 'Solo se validará el número' }}
            </p>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900">Cómo se resuelve una consulta</h2>
            <p class="mt-0.5 text-xs text-gray-500">De arriba abajo. Se para en el primero que responde.</p>
        </div>

        <ol class="divide-y divide-gray-100 text-sm">
            <li class="flex items-start gap-3 px-5 py-3">
                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gray-900 text-[11px] font-semibold text-white">1</span>
                <div>
                    <p class="font-medium text-gray-900">Dígito verificador</p>
                    <p class="text-xs text-gray-500">Un RUC mal tecleado se detecta aquí, sin salir a internet. No gasta consulta.</p>
                </div>
            </li>
            <li class="flex items-start gap-3 px-5 py-3">
                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gray-900 text-[11px] font-semibold text-white">2</span>
                <div>
                    <p class="font-medium text-gray-900">Padrón y consultas guardadas</p>
                    <p class="text-xs text-gray-500">Lo que ya está en casa. Los clientes facturan a los mismos RUC una y otra vez.</p>
                </div>
            </li>
            <li class="flex items-start gap-3 px-5 py-3">
                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gray-900 text-[11px] font-semibold text-white">3</span>
                <div>
                    <p class="font-medium text-gray-900">Proveedor externo</p>
                    <p class="text-xs text-gray-500">Solo si no estaba. El resultado se guarda para la próxima vez.</p>
                </div>
            </li>
        </ol>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900">Proveedor</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                SUNAT no publica una API abierta para esto. Si lo dejas vacío, el sistema seguirá validando
                el número pero no traerá el nombre ni la dirección.
            </p>
        </div>

        <form method="POST" action="{{ route('super-admin.consultas.update') }}" class="space-y-4 p-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="consultas_url" class="mb-1 block text-sm font-medium text-gray-700">Dirección base</label>
                    <input type="url" name="consultas_url" id="consultas_url"
                           value="{{ old('consultas_url', $ajustes['consultas_url'] ?? '') }}"
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="https://mi-api.com/consultas/api">
                    <p class="mt-1 text-xs text-gray-500">
                        Se le añade <code class="rounded bg-gray-100 px-1">/ruc/&lt;número&gt;</code> o
                        <code class="rounded bg-gray-100 px-1">/dni/&lt;número&gt;</code>
                    </p>
                </div>
                <div>
                    <label for="consultas_token" class="mb-1 block text-sm font-medium text-gray-700">Token</label>
                    <input type="password" name="consultas_token" id="consultas_token" autocomplete="new-password"
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="{{ !empty($ajustes['consultas_token']) ? 'Dejar vacío para mantener el actual' : 'Opcional' }}">
                    <p class="mt-1 text-xs text-gray-500">Viaja como <code class="rounded bg-gray-100 px-1">Authorization: Bearer</code></p>
                </div>
            </div>

            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700">
                Guardar
            </button>
        </form>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900">Probar</h2>
            <p class="mt-0.5 text-xs text-gray-500">Salta la caché y pregunta de verdad, para ver si el proveedor responde.</p>
        </div>

        <div class="space-y-4 p-5">
            <form method="POST" action="{{ route('super-admin.consultas.probar') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label for="tipo" class="mb-1 block text-sm font-medium text-gray-700">Tipo</label>
                    <select name="tipo" id="tipo" class="rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="ruc">RUC</option>
                        <option value="dni">DNI</option>
                    </select>
                </div>
                <div>
                    <label for="numero" class="mb-1 block text-sm font-medium text-gray-700">Número</label>
                    <input type="text" name="numero" id="numero" value="{{ old('numero') }}"
                           class="w-56 rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="20608251589">
                </div>
                <button type="submit" class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800">
                    Consultar
                </button>
            </form>

            @if($r = session('consulta_prueba'))
                <div class="rounded-lg border px-4 py-3 text-sm {{ $r['valido'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                    <p class="font-semibold {{ $r['valido'] ? 'text-green-800' : 'text-red-800' }}">
                        {{ $r['numero'] }} — {{ $r['valido'] ? 'válido' : 'no válido' }}
                        @if(!empty($r['fuente']))
                            <span class="ml-1 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-600">{{ $r['fuente'] }}</span>
                        @endif
                    </p>

                    <dl class="mt-2 grid gap-x-6 gap-y-1 text-xs sm:grid-cols-2">
                        @foreach($r as $campo => $valor)
                            @continue(in_array($campo, ['valido', 'numero', 'tipo', 'fuente'], true) || $valor === null)
                            <div class="flex gap-2">
                                <dt class="w-28 shrink-0 text-gray-500">{{ str_replace('_', ' ', $campo) }}</dt>
                                <dd class="font-medium text-gray-800">{{ $valor }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif
        </div>
    </section>

    @if($cache['total'] > 0)
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-gray-900">Vaciar las consultas guardadas</p>
                    <p class="mt-0.5 text-xs text-gray-500">
                        Se volverán a pedir al proveedor la próxima vez. Útil si un dato cambió en SUNAT.
                    </p>
                </div>
                <form method="POST" action="{{ route('super-admin.consultas.cache.vaciar') }}"
                      onsubmit="return confirm('Se borrarán {{ $cache['total'] }} consultas guardadas. ¿Continuar?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50">
                        Vaciar
                    </button>
                </form>
            </div>
        </section>
    @endif

</div>
@endsection
