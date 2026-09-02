@extends('layouts.app')

@section('title', 'Sandbox Facturación')

@section('content')
<div class="space-y-5">

    <div>
        <h1 class="text-2xl font-bold text-gray-800">Sandbox Facturación</h1>
        <p class="mt-1 text-sm text-gray-500">
            Credenciales para que un programador integre con tu API. Emiten contra SUNAT beta:
            los comprobantes no tienen valor legal y no consumen cupo.
        </p>
    </div>

    {{-- Las credenciales del token, la unica vez que se pueden leer. --}}
    @if(session('credenciales_nuevas'))
        @include('_credenciales_nuevas', ['credenciales' => session('credenciales_nuevas')])
    @elseif(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    @unless($empresaDemo)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            No tienes ninguna empresa marcada como demo, así que todavía no se pueden generar tokens.
            Entra a <a href="{{ route('super-admin.companies.index') }}" class="font-medium underline">Empresas</a>,
            abre el detalle de una y márcala como demo.
        </div>
    @endunless

    {{-- Alta --}}
    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-semibold text-gray-900">Generar un token</h2>
        </div>

        <form method="POST" action="{{ route('super-admin.tokens-prueba.store') }}"
              class="flex flex-wrap items-end gap-3 px-5 py-4">
            @csrf
            <label class="text-sm font-medium text-gray-700">
                Programador o proyecto
                <input type="text" name="dev_name" required maxlength="120" value="{{ old('dev_name') }}"
                       placeholder="Ej.: Juan Pérez (ERP Contable)"
                       class="mt-1 block w-72 rounded-md border border-gray-300 px-3 py-2 text-sm">
            </label>
            <label class="text-sm font-medium text-gray-700">
                Caduca en
                <div class="mt-1 flex items-center gap-2">
                    <input type="number" name="expires_in_days" min="1" max="365" placeholder="30" value="{{ old('expires_in_days') }}"
                           class="w-24 rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <span class="text-sm font-normal text-gray-500">días (opcional)</span>
                </div>
            </label>
            <button type="submit" @disabled(! $empresaDemo)
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:bg-gray-300">
                Generar token
            </button>
        </form>
    </div>

    {{-- Listado --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-semibold text-gray-900">Tokens entregados</h2>
            <p class="mt-0.5 text-sm text-gray-500">{{ $tokens->count() }} en total.</p>
        </div>

        @if($tokens->isEmpty())
            <p class="px-5 py-12 text-center text-sm text-gray-500">
                Todavía no has entregado ningún token de prueba.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Programador</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Llamadas</th>
                            <th class="px-4 py-3">Último uso</th>
                            <th class="px-4 py-3">Caduca</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tokens as $token)
                            <tr class="border-t border-gray-100">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-gray-900">{{ \Illuminate\Support\Str::after($token->name, 'Sandbox - ') }}</p>
                                    <p class="font-mono text-xs text-gray-400">{{ \Illuminate\Support\Str::limit($token->key, 22) }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $token->active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $token->active ? 'Activo' : 'Bloqueado' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ number_format($consumo[$token->id] ?? 0) }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    {{ optional($token->last_used_at)->diffForHumans() ?? 'Nunca' }}
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    @if (! $token->expires_at)
                                        <span class="text-gray-400">Sin caducidad</span>
                                    @elseif ($token->expires_at->isPast())
                                        <span class="font-medium text-red-600">Expiró el {{ $token->expires_at->format('d/m/Y') }}</span>
                                    @else
                                        <span class="text-gray-600">{{ $token->expires_at->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <x-icon-action icon="ver" label="Ver credenciales" color="blue" type="button"
                                                       onclick="window.openAdminModal('{{ route('super-admin.api-global.show-key', $token) }}', 'Credenciales del token')" />

                                        {{-- El secret no se puede releer, asi que al que lo pierde
                                             se le da uno nuevo. La key no cambia. --}}
                                        <form method="POST" action="{{ route('super-admin.tokens-prueba.regenerar', $token) }}"
                                              onsubmit="return confirm('Se genera un secret nuevo para «{{ $token->name }}».{{ chr(10) }}{{ chr(10) }}El actual deja de funcionar al instante, asi que hay que pasarle el nuevo al programador. La X-Api-Key no cambia.{{ chr(10) }}{{ chr(10) }}Seguir?')">
                                            @csrf
                                            <x-icon-action icon="renovar" label="Generar un secret nuevo" color="amber" />
                                        </form>

                                        {{-- Aparte de las credenciales: son dos preguntas distintas
                                             ("que le doy al dev" y "que le esta fallando"). --}}
                                        <x-icon-action icon="actividad" label="Ver actividad y errores" color="violet" type="button"
                                                       onclick="window.openAdminModal('{{ route('super-admin.api-global.key-actividad', $token) }}', 'Actividad del token')" />

                                        <form method="POST" action="{{ route('super-admin.api-global.toggle-key', $token) }}"
                                              onsubmit="return confirm('{{ $token->active ? 'Bloquear este token? El programador dejara de poder emitir.' : 'Activar este token?' }}')">
                                            @csrf
                                            <x-icon-action :icon="$token->active ? 'bloquear' : 'desbloquear'"
                                                           :label="$token->active ? 'Bloquear token' : 'Activar token'"
                                                           :color="$token->active ? 'amber' : 'emerald'" />
                                        </form>

                                        <form method="POST" action="{{ route('super-admin.api-global.extend-key', $token) }}"
                                              class="flex items-center gap-1">
                                            @csrf
                                            <input type="number" name="days" value="30" min="1" max="365" title="Días a añadir"
                                                   class="w-14 rounded border border-gray-300 px-1.5 py-1 text-xs">
                                            <x-icon-action icon="renovar" label="Extender caducidad" color="slate" />
                                        </form>

                                        <form method="POST" action="{{ route('super-admin.api-global.delete-key', $token) }}"
                                              onsubmit="return confirm('Eliminar este token? Es permanente.')">
                                            @csrf
                                            @method('DELETE')
                                            <x-icon-action icon="eliminar" label="Eliminar token" color="red" />
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="border-t border-gray-100 px-5 py-3 text-xs text-gray-500">
            URL base que necesita el programador:
            <code class="ml-1 rounded bg-gray-100 px-2 py-0.5 font-mono text-gray-700">{{ url('/api') }}</code>
            · La documentación pública está en
            <a href="{{ url('/docs') }}" target="_blank" rel="noopener" class="font-medium text-blue-600 hover:underline">{{ url('/docs') }}</a>
        </div>
    </div>
</div>
@endsection
