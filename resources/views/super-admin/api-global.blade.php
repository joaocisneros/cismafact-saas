@extends('layouts.app')

@section('title', 'API')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Administración de API</h2>
            <p class="mt-1 text-sm text-gray-500">Resumen ligero del servicio y accesos bajo demanda.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button"
                    onclick="window.openAdminModal('{{ route('super-admin.api-global.api-keys') }}', 'API Keys')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                API Keys
            </button>
            <button type="button"
                    onclick="window.openAdminModal('{{ route('super-admin.api-global.performance') }}', 'Rendimiento de API')"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Rendimiento
            </button>
            <button type="button"
                    onclick="window.openAdminModal('{{ route('super-admin.api-global.logs') }}', 'Últimos logs de API')"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Ver logs
            </button>
        </div>
    </div>

    <div class="overflow-hidden border-y border-gray-200 bg-white">
        <dl class="grid grid-cols-2 divide-x divide-y divide-gray-200 md:grid-cols-4 md:divide-y-0">
            <div class="p-4">
                <dt class="text-xs font-medium uppercase text-gray-500">Solicitudes hoy</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($consumoHoy) }}</dd>
            </div>
            <div class="p-4">
                <dt class="text-xs font-medium uppercase text-gray-500">Solicitudes del mes</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($consumoMes) }}</dd>
            </div>
            <div class="p-4">
                <dt class="text-xs font-medium uppercase text-gray-500">Errores hoy</dt>
                <dd class="mt-1 text-2xl font-semibold {{ $erroresHoy > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ number_format($erroresHoy) }}</dd>
            </div>
            <div class="p-4">
                <dt class="text-xs font-medium uppercase text-gray-500">Tiempo promedio</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($tiempoPromedio, 0) }} ms</dd>
                <p class="mt-1 text-xs text-gray-500">{{ number_format($apiKeyActivas) }} API keys activas</p>
            </div>
        </dl>
    </div>

    <div class="border-y border-gray-200 bg-white px-5 py-6">
        <h3 class="text-sm font-semibold text-gray-900">Estado del servicio</h3>
        <div class="mt-3 flex items-center gap-2 text-sm text-green-700">
            <span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
            API disponible para recibir solicitudes
        </div>
    </div>

    {{-- ===== Token Sandbox (pruebas para programadores) ===== --}}
    <div class="border-y border-gray-200 bg-white px-5 py-6">
        <h3 class="text-sm font-semibold text-gray-900">Token Sandbox (pruebas)</h3>
        <p class="mt-1 text-sm text-gray-500">
            Genera un token de prueba para entregárselo a un programador. Emite solo a <strong>SUNAT beta</strong> (documentos sin valor legal), sin tope, ligado a tu empresa demo. Cada token lleva el nombre del dev para ver su consumo y bloquearlo individualmente.
        </p>
        <p class="mt-2 text-sm text-gray-600">
            URL Base de la API (el dev la necesita):
            <span class="ml-1 break-all rounded bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-800">{{ url('/api') }}</span>
        </p>

        @if (session('sandbox_token'))
            @php($st = session('sandbox_token'))
            <div class="mt-4 rounded-md border border-green-300 bg-green-50 p-4">
                <p class="text-sm font-semibold text-green-800">✅ {{ $st['name'] }} — cópialo ahora (el secreto no se vuelve a mostrar):</p>
                <dl class="mt-3 space-y-2 text-sm">
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">URL Base de la API</dt>
                        <dd class="mt-1 break-all rounded bg-white px-3 py-2 font-mono text-gray-800 ring-1 ring-gray-200">{{ url('/api') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">X-Api-Key</dt>
                        <dd class="mt-1 break-all rounded bg-white px-3 py-2 font-mono text-gray-800 ring-1 ring-gray-200">{{ $st['key'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">X-Api-Secret</dt>
                        <dd class="mt-1 break-all rounded bg-white px-3 py-2 font-mono text-gray-800 ring-1 ring-gray-200">{{ $st['secret'] }}</dd>
                    </div>
                </dl>
            </div>
        @endif

        @if (session('error'))
            <div class="mt-4 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('super-admin.api-global.sandbox-token') }}" class="mt-4 flex flex-wrap items-end gap-3">
            @csrf
            <label class="text-sm font-medium text-gray-700">
                Nombre del dev / proyecto
                <input type="text" name="dev_name" required placeholder="Ej: Juan Pérez (ERP X)"
                       class="mt-1 block w-64 rounded-md border border-gray-300 px-3 py-2 text-sm">
            </label>
            <label class="text-sm font-medium text-gray-700">
                Caduca en (días, opcional)
                <input type="number" name="expires_in_days" min="1" max="365" placeholder="Ej: 30"
                       class="mt-1 block w-44 rounded-md border border-gray-300 px-3 py-2 text-sm">
            </label>
            <button type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Generar token sandbox
            </button>
        </form>

        {{-- Lista de tokens sandbox: ver / extender / bloquear aquí mismo --}}
        <div class="mt-6">
            <h4 class="text-xs font-semibold uppercase text-gray-500">Tokens sandbox generados</h4>
            @if (empty($sandboxTokens) || $sandboxTokens->isEmpty())
                <p class="mt-2 text-sm text-gray-400">Aún no has generado tokens de prueba. Si el botón no funciona, primero marca una empresa como demo (Empresas → detalle → "Marcar como demo").</p>
            @else
                <div class="mt-2 overflow-x-auto border-y border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-3 py-2">Dev / Token</th>
                                <th class="px-3 py-2">Estado</th>
                                <th class="px-3 py-2">Último uso</th>
                                <th class="px-3 py-2">Caduca</th>
                                <th class="px-3 py-2">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($sandboxTokens as $token)
                                <tr>
                                    <td class="px-3 py-2">
                                        <div class="font-medium text-gray-900">{{ $token->name }}</div>
                                        <div class="font-mono text-xs text-gray-400">{{ \Illuminate\Support\Str::limit($token->key, 18) }}</div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="rounded px-2 py-0.5 text-xs {{ $token->active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $token->active ? 'Activo' : 'Bloqueado' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-xs text-gray-500">{{ optional($token->last_used_at)->diffForHumans() ?? 'Nunca' }}</td>
                                    <td class="px-3 py-2 text-xs">
                                        @if (! $token->expires_at)
                                            <span class="text-gray-400">Sin caducidad</span>
                                        @elseif ($token->expires_at->isPast())
                                            <span class="font-medium text-red-600">Expirado {{ $token->expires_at->format('d/m/Y') }}</span>
                                        @else
                                            <span class="text-gray-600">{{ $token->expires_at->format('d/m/Y') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" onclick="window.openAdminModal('{{ route('super-admin.api-global.show-key', $token) }}', 'Token sandbox')" class="text-sm text-blue-600 hover:underline">Ver</button>
                                            <form method="POST" action="{{ route('super-admin.api-global.toggle-key', $token) }}" onsubmit="return confirm('Cambiar estado del token?')">
                                                @csrf
                                                <button type="submit" class="text-sm text-{{ $token->active ? 'red' : 'green' }}-600 hover:underline">
                                                    {{ $token->active ? 'Bloquear' : 'Activar' }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('super-admin.api-global.extend-key', $token) }}" class="flex items-center gap-1">
                                                @csrf
                                                <input type="number" name="days" value="30" min="1" max="365"
                                                       class="w-14 rounded border border-gray-300 px-1 py-0.5 text-xs">
                                                <button type="submit" class="text-sm text-indigo-600 hover:underline">Extender</button>
                                            </form>
                                            <form method="POST" action="{{ route('super-admin.api-global.delete-key', $token) }}" onsubmit="return confirm('¿Eliminar este token definitivamente? No se puede deshacer.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm text-gray-500 hover:text-red-600 hover:underline">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
