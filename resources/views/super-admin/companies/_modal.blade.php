@php
    $totalDocs = $company->invoices_count + $company->boletas_count + $company->credit_notes_count + $company->debit_notes_count + $company->dispatch_guides_count;
@endphp

<div class="space-y-5 p-5">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
        <div>
            <h4 class="text-lg font-semibold text-gray-900">{{ $company->razon_social }}</h4>
            <p class="mt-1 text-sm text-gray-500">RUC {{ $company->ruc }} · {{ $company->email ?? 'Sin correo' }}</p>
        </div>
        <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold {{ $company->activo ? 'bg-green-50 text-green-700 ring-1 ring-green-600/20' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20' }}">
            {{ $company->activo ? 'Activa' : 'Suspendida' }}
        </span>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <div class="rounded-lg bg-blue-50 p-3 text-blue-800">
            <p class="text-xs font-medium">Plan</p>
            <p class="mt-1 text-lg font-semibold">{{ $company->plan->name ?? 'Sin plan' }}</p>
        </div>
        <div class="rounded-lg bg-gray-50 p-3 text-gray-800">
            <p class="text-xs font-medium">Documentos</p>
            <p class="mt-1 text-lg font-semibold">{{ number_format($totalDocs) }}</p>
        </div>
        <div class="rounded-lg bg-emerald-50 p-3 text-emerald-800">
            <p class="text-xs font-medium">API Keys</p>
            <p class="mt-1 text-lg font-semibold">{{ number_format($company->api_keys_count) }}</p>
        </div>
        <div class="rounded-lg bg-violet-50 p-3 text-violet-800">
            <p class="text-xs font-medium">Usuarios</p>
            <p class="mt-1 text-lg font-semibold">{{ number_format($company->users_count) }}</p>
        </div>
        <div class="rounded-lg bg-amber-50 p-3 text-amber-800">
            <p class="text-xs font-medium">API Mes</p>
            <p class="mt-1 text-lg font-semibold">{{ number_format($apiUsage['month']) }}</p>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <div>
            <h5 class="mb-3 text-sm font-semibold text-gray-900">Datos</h5>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Registro</dt><dd class="font-medium">{{ $company->created_at->format('d/m/Y H:i') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Telefono</dt><dd class="font-medium">{{ $company->telefono ?? '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Direccion</dt><dd class="max-w-[260px] truncate font-medium">{{ $company->direccion ?? '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Modo</dt><dd class="font-medium">{{ $company->modo_produccion ? 'Produccion' : 'Demo/Beta' }}</dd></div>
            </dl>
        </div>

        <div>
            <h5 class="mb-3 text-sm font-semibold text-gray-900">API Keys</h5>
            <div class="space-y-2">
                @forelse($apiKeys->take(4) as $key)
                    <div class="rounded-md bg-gray-50 px-3 py-2">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900">{{ $key->name }}</p>
                            <span class="rounded px-2 py-1 text-xs {{ $key->active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $key->active ? 'Activa' : 'Inactiva' }}</span>
                        </div>
                        <div class="space-y-2">
                            <div>
                                <p class="text-[11px] font-semibold uppercase text-gray-500">X-Api-Key</p>
                                <div class="mt-1 flex items-center gap-2 rounded bg-white px-2 py-1">
                                    <code class="min-w-0 flex-1 truncate font-mono text-xs text-gray-700">{{ $key->key }}</code>
                                    <button type="button"
                                            onclick="window.copyCompanyCredential(this, @js($key->key))"
                                            class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                        Copiar
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold uppercase text-gray-500">X-Api-Secret</p>
                                <div class="mt-1 flex items-center gap-2 rounded bg-white px-2 py-1">
                                    @if($key->plain_secret)
                                        <code class="min-w-0 flex-1 truncate font-mono text-xs text-gray-700">{{ $key->plain_secret }}</code>
                                        <button type="button"
                                                onclick="window.copyCompanyCredential(this, @js($key->plain_secret))"
                                                class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                            Copiar
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
    </div>

    <div class="flex flex-wrap justify-end gap-2 border-t border-gray-200 pt-4">
        <button type="button"
                onclick="window.openAdminModal('{{ route('super-admin.companies.edit', $company) }}?modal=1', 'Editar empresa')"
                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            Editar
        </button>
    </div>
</div>
