@extends('layouts.app')

@section('title', 'Auditoría')

@section('content')
<div class="space-y-5">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Auditoría</h2>
        <p class="mt-1 text-sm text-gray-500">Registro de acciones realizadas en el panel administrativo y en cada empresa.</p>
    </div>

    @php
        $hayFiltros = collect(['search', 'user_id', 'company_id', 'date_from', 'date_to'])
            ->contains(fn ($campo) => request()->filled($campo));
    @endphp
    <form method="GET" class="grid items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 md:grid-cols-3 xl:grid-cols-6">
        <label>
            <span class="mb-1 block text-xs font-medium text-gray-600">Buscar</span>
            <input name="search" value="{{ request('search') }}" placeholder="Correo, RUC, DNI, factura…"
                   @input.debounce.600ms="$el.form.requestSubmit()"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </label>
        <label>
            <span class="mb-1 block text-xs font-medium text-gray-600">Responsable</span>
            <select name="user_id" onchange="this.form.requestSubmit()" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="mb-1 block text-xs font-medium text-gray-600">Empresa afectada</span>
            <select name="company_id" onchange="this.form.requestSubmit()" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">Todas</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->razon_social }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="mb-1 block text-xs font-medium text-gray-600">Desde</span>
            <input type="date" name="date_from" value="{{ request('date_from') }}" onchange="this.form.requestSubmit()" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </label>
        <label>
            <span class="mb-1 block text-xs font-medium text-gray-600">Hasta</span>
            <input type="date" name="date_to" value="{{ request('date_to') }}" onchange="this.form.requestSubmit()" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </label>
        <div class="flex items-center justify-end gap-2">
            <span class="text-xs text-gray-400">Aplicación automática</span>
            @if($hayFiltros)
                <a href="{{ route('super-admin.audit.index') }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Limpiar</a>
            @endif
        </div>
    </form>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        {{-- Las columnas conservan siempre el mismo ancho. Al filtrar cambian
             los registros, pero nunca la posición de la información. --}}
        <table class="w-full min-w-[1840px] table-fixed divide-y divide-gray-200 text-sm">
            <colgroup>
                <col class="w-[150px]">
                <col class="w-[250px]">
                <col class="w-[280px]">
                <col class="w-[190px]">
                <col class="w-[220px]">
                <col class="w-[190px]">
                <col class="w-[260px]">
                <col class="w-[200px]">
                <col class="w-[100px]">
            </colgroup>
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Fecha y hora</th>
                    <th class="px-4 py-3">Responsable</th>
                    <th class="px-4 py-3">Acción realizada</th>
                    <th class="px-4 py-3">Servicio / entorno</th>
                    <th class="px-4 py-3">Empresa afectada</th>
                    <th class="px-4 py-3">Origen</th>
                    <th class="bg-amber-50/70 px-4 py-3 text-amber-800">Cuenta administrada</th>
                    <th class="px-4 py-3">IP / dispositivo</th>
                    <th class="px-4 py-3">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 align-top text-gray-600">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="min-w-0 px-4 py-3 align-top">
                            @php
                                $nombreActor = \App\Support\AccionAuditada::nombreActor($log);
                                $correoActor = \App\Support\AccionAuditada::correoActor($log);
                                $esSoporte = \App\Support\AccionAuditada::enSoporte($log);
                                $cuentaUtilizada = \App\Support\AccionAuditada::cuentaUtilizada($log);
                            @endphp
                            <div class="flex items-start gap-2.5">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8zm7 0l2 2 4-4"/></svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="font-semibold text-gray-900">{{ $nombreActor }}</span>
                                        @if($esSoporte)
                                            <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-blue-700">Soporte</span>
                                        @endif
                                    </div>
                                    <div class="break-all text-xs text-gray-500">{{ $correoActor ?? 'Sin correo registrado' }}</div>
                                </div>
                            </div>
                        </td>
                        {{-- La frase, no el nombre interno de la ruta: al revisar el
                             registro lo que hace falta saber es que paso. --}}
                        <td class="px-4 py-3 align-top">
                            <p class="font-medium text-gray-900">{{ \App\Support\AccionAuditada::describir($log) }}</p>
                            @if($log->target_name)
                                <p class="mt-1 break-words text-xs text-gray-500">Afectó a: <span class="font-medium text-gray-700">{{ $log->target_name }}</span></p>
                            @endif
                            @if($log->target_email && $log->target_email !== $correoActor)
                                <p class="mt-1 break-all text-xs text-gray-500">Correo afectado: <span class="font-medium text-gray-700">{{ $log->target_email }}</span></p>
                            @endif
                        </td>

                        @php($contexto = \App\Support\AccionAuditada::contexto($log))
                        <td class="px-4 py-3 align-top">
                            <div class="font-medium text-gray-800">{{ $contexto['servicio'] }}</div>
                            <div class="mt-1 flex flex-wrap gap-1">
                                @if($contexto['entorno'])
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $contexto['entorno'] === 'Producción' ? 'bg-blue-50 text-blue-700' : 'bg-violet-50 text-violet-700' }}">
                                        {{ $contexto['entorno'] }}
                                    </span>
                                @endif
                                @if($contexto['tipo'])
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700">{{ $contexto['tipo'] }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="break-words px-4 py-3 align-top text-gray-600">{{ $log->company->razon_social ?? 'Acción general del sistema' }}</td>

                        {{-- El nombre de la ruta se sigue enseñando aqui, en pequeño:
                             cuando hay que rastrear algo en el codigo es lo unico
                             que sirve. --}}
                        <td class="min-w-0 px-4 py-3 align-top" title="{{ $log->route_name }} · {{ $log->path }}">
                            <div class="truncate font-medium text-gray-800">{{ \App\Support\AccionAuditada::modulo($log) }}</div>
                            <div class="truncate text-xs text-gray-500">{{ \App\Support\AccionAuditada::panel($log) }}</div>
                        </td>
                        <td class="px-4 py-3 align-top {{ $cuentaUtilizada ? 'bg-amber-50/50' : 'bg-gray-50/40' }}">
                            @if($cuentaUtilizada)
                                <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-800">Acceso por soporte</span>
                                <div class="mt-2 font-semibold text-gray-900">{{ $cuentaUtilizada['nombre'] }}</div>
                                <div class="mt-0.5 break-all text-xs text-gray-600">{{ $cuentaUtilizada['correo'] ?? 'Sin correo registrado' }}</div>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-600">Acción directa</span>
                                <div class="mt-2 text-xs text-gray-500">Sin sesión de soporte</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-top">
                            @php($dispositivo = \App\Support\AccionAuditada::dispositivo($log))
                            <div class="flex items-center gap-1.5 font-mono text-xs text-gray-600" title="Dirección desde la que se realizó la acción">
                                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.2-2.46 3.33-5.46 3.4-9C15.33 8.46 14.2 5.46 12 3m0 18c-2.2-2.46-3.33-5.46-3.4-9C8.67 8.46 9.8 5.46 12 3M3.6 9h16.8M3.6 15h16.8"/></svg>
                                {{ $log->ip_address ?? 'Sin registrar' }}
                            </div>
                            <div class="mt-2 flex items-start gap-1.5 text-xs text-gray-500" title="{{ $log->user_agent }}">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span><strong class="font-medium text-gray-700">{{ $dispositivo['tipo'] }}</strong><br>{{ $dispositivo['navegador'] }} · {{ $dispositivo['sistema'] }}</span>
                            </div>
                        </td>
                        {{-- «HTTP 302» era el codigo con el que el servidor devuelve
                             a la pantalla despues de guardar: queria decir que salio
                             bien, pero habia que saberlo. --}}
                        @php($resultado = \App\Support\AccionAuditada::resultado($log))
                        <td class="px-4 py-3 align-top" title="{{ $resultado['detalle'] }} (HTTP {{ $log->response_status }})">
                            <span class="font-medium {{ $resultado['tono'] }}">{{ $resultado['texto'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-10 text-center text-gray-500">Aún no hay acciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $logs->links() }}</div>
</div>
@endsection
