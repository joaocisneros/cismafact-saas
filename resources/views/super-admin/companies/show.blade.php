@extends('layouts.app')

@section('title', $company->razon_social)

@section('content')
@php
    $totalDocs = $company->invoices_count + $company->boletas_count + $company->credit_notes_count + $company->debit_notes_count + $company->dispatch_guides_count;
@endphp

<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
        <div>
            <a href="{{ route('super-admin.companies.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">Volver a empresas</a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h2 class="text-xl font-semibold text-gray-900">{{ $company->razon_social }}</h2>
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $company->activo ? 'bg-green-50 text-green-700 ring-1 ring-green-600/20' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20' }}">
                    {{ $company->activo ? 'Activa' : 'Suspendida' }}
                </span>
            </div>
            <p class="mt-1 text-sm text-gray-500">RUC {{ $company->ruc }} · Registrada {{ $company->created_at->format('d/m/Y') }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('super-admin.companies.edit', $company) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Editar</a>
            <form method="POST" action="{{ route('super-admin.companies.toggle-status', $company) }}">
                @csrf
                <button type="submit" class="rounded-lg px-4 py-2 text-sm text-white {{ $company->activo ? 'bg-amber-600 hover:bg-amber-700' : 'bg-green-600 hover:bg-green-700' }}">
                    {{ $company->activo ? 'Suspender' : 'Reactivar' }}
                </button>
            </form>
            <a href="{{ route('super-admin.documents', ['company_id' => $company->id]) }}" class="rounded-lg bg-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-300">Ver Documentos</a>
            <button type="button" onclick="document.getElementById('modal-eliminar').classList.remove('hidden')" class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">Eliminar</button>
        </div>
    </div>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Plan Asignado</p>
            <p class="mt-3 text-2xl font-semibold text-gray-900">{{ $company->plan->name ?? 'Sin plan' }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Documentos Emitidos</p>
            <p class="mt-3 text-2xl font-semibold text-gray-900">{{ number_format($totalDocs) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">API Keys Generadas</p>
            <p class="mt-3 text-2xl font-semibold text-gray-900">{{ number_format($company->api_keys_count) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">API Requests Mes</p>
            <p class="mt-3 text-2xl font-semibold text-gray-900">{{ number_format($apiUsage['month']) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Usuarios</p>
            <p class="mt-3 text-2xl font-semibold text-gray-900">{{ number_format($company->users_count) }}</p>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-xl bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-base font-semibold text-gray-900">Datos de la Empresa</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">RUC</dt><dd class="font-medium text-gray-900">{{ $company->ruc }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Razon Social</dt><dd class="font-medium text-gray-900">{{ $company->razon_social }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Correo</dt><dd class="font-medium text-gray-900">{{ $company->email ?? '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Telefono</dt><dd class="font-medium text-gray-900">{{ $company->telefono ?? '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Plan</dt><dd class="font-medium text-gray-900">{{ $company->plan->name ?? 'Sin plan' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Estado</dt><dd class="font-medium text-gray-900">{{ $company->activo ? 'Activa' : 'Suspendida' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Fecha Registro</dt><dd class="font-medium text-gray-900">{{ $company->created_at->format('d/m/Y H:i') }}</dd></div>
            </dl>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-base font-semibold text-gray-900">Resumen Documentos</h3>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-lg bg-blue-50 p-3 text-blue-800"><p>Facturas</p><strong>{{ number_format($company->invoices_count) }}</strong></div>
                <div class="rounded-lg bg-green-50 p-3 text-green-800"><p>Boletas</p><strong>{{ number_format($company->boletas_count) }}</strong></div>
                <div class="rounded-lg bg-amber-50 p-3 text-amber-800"><p>Notas Credito</p><strong>{{ number_format($company->credit_notes_count) }}</strong></div>
                <div class="rounded-lg bg-red-50 p-3 text-red-800"><p>Notas Debito</p><strong>{{ number_format($company->debit_notes_count) }}</strong></div>
                <div class="rounded-lg bg-violet-50 p-3 text-violet-800"><p>Guias Remision</p><strong>{{ number_format($company->dispatch_guides_count) }}</strong></div>
                <div class="rounded-lg bg-gray-50 p-3 text-gray-800"><p>Total</p><strong>{{ number_format($totalDocs) }}</strong></div>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-xl bg-white p-6 shadow-sm" x-data="{ copying: null, copy(value, id) { navigator.clipboard.writeText(value); this.copying = id; setTimeout(() => this.copying = null, 1500); } }">
            <h3 class="mb-4 text-base font-semibold text-gray-900">API Keys Generadas</h3>
            <div class="space-y-3">
                @forelse($apiKeys as $key)
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $key->name }}</p>
                                <p class="text-xs text-gray-500">{{ $key->last_used_at ? 'Ultimo uso ' . $key->last_used_at->diffForHumans() : 'Sin uso' }}</p>
                            </div>
                            <span class="rounded px-2 py-1 text-xs {{ $key->active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $key->active ? 'Activa' : 'Inactiva' }}</span>
                        </div>
                        <div class="space-y-2">
                            <div>
                                <p class="mb-1 text-[11px] font-semibold uppercase text-gray-500">X-Api-Key</p>
                                <div class="flex items-center gap-2 rounded-md bg-white px-3 py-2">
                                    <code class="min-w-0 flex-1 truncate font-mono text-xs text-gray-800">{{ $key->key }}</code>
                                    <button type="button" @click="copy(@js($key->key), 'key-{{ $key->id }}')" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                        <span x-text="copying === 'key-{{ $key->id }}' ? 'Copiado' : 'Copiar'"></span>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="mb-1 text-[11px] font-semibold uppercase text-gray-500">X-Api-Secret</p>
                                <div class="flex items-center gap-2 rounded-md bg-white px-3 py-2">
                                    @if($key->plain_secret)
                                        <code class="min-w-0 flex-1 truncate font-mono text-xs text-gray-800">{{ $key->plain_secret }}</code>
                                        <button type="button" @click="copy(@js($key->plain_secret), 'secret-{{ $key->id }}')" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                            <span x-text="copying === 'secret-{{ $key->id }}' ? 'Copiado' : 'Copiar'"></span>
                                        </button>
                                    @else
                                        <span class="text-xs text-amber-700">Secret antiguo no recuperable. Regenera para verlo.</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No hay API keys generadas.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-base font-semibold text-gray-900">Consumo API</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Requests hoy</dt><dd class="font-medium">{{ number_format($apiUsage['today']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Requests mes</dt><dd class="font-medium">{{ number_format($apiUsage['month']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Respuesta promedio 30 dias</dt><dd class="font-medium">{{ number_format($apiUsage['avg_response']) }} ms</dd></div>
            </dl>

            <h3 class="mb-3 mt-6 text-base font-semibold text-gray-900">Usuarios Recientes</h3>
            <div class="space-y-3">
                @forelse($users as $user)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $user->email }}</p>
                        </div>
                        <span class="rounded px-2 py-1 text-xs {{ $user->active ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $user->active ? 'Activo' : 'Suspendido' }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No hay usuarios asignados.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="rounded-xl bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-base font-semibold text-gray-900">Documentos Recientes</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500">
                        <th class="pb-3 font-medium">Tipo</th>
                        <th class="pb-3 font-medium">Documento</th>
                        <th class="pb-3 font-medium">Estado</th>
                        <th class="pb-3 font-medium">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentDocs as $doc)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3">{{ $doc->tipo }}</td>
                            <td class="py-3 font-medium">{{ $doc->numero_completo }}</td>
                            <td class="py-3"><x-status-badge :status="$doc->estado_sunat" /></td>
                            <td class="py-3 text-gray-500">{{ \Illuminate\Support\Carbon::parse($doc->created_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-500">No hay documentos emitidos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div id="modal-eliminar" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
    <div class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <h3 class="mb-2 text-lg font-semibold text-gray-800">Eliminar Empresa</h3>
        <p class="mb-6 text-sm text-gray-600">Eliminar <strong>{{ $company->razon_social }}</strong>? Esta accion no se puede deshacer.</p>
        <div class="flex justify-end gap-3">
            <button onclick="document.getElementById('modal-eliminar').classList.add('hidden')" class="rounded-lg bg-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-300">Cancelar</button>
            <form method="POST" action="{{ route('super-admin.companies.destroy', $company) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">Eliminar</button>
            </form>
        </div>
    </div>
</div>
@endsection
