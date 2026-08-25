@php
    // El contador entra a esta pantalla solo para consultar: crear, editar,
    // suspender o eliminar empresas es cosa del Super Admin. Si no se esconden
    // los botones, al pulsarlos el servidor lo rebota y el modal sale vacio.
    $esSuperAdmin = auth()->user()->hasRole('super_admin');
@endphp

@extends('layouts.app')

@section('title', 'Empresas')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Empresas</h2>
            <p class="mt-1 text-sm text-gray-500">Listado rapido de empresas, planes, documentos y API keys.</p>
        </div>
        <button type="button" onclick="window.openAdminModal('{{ route('super-admin.companies.create') }}?modal=1', 'Crear empresa')" class="whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Crear Empresa</button>
    </div>

    <form method="GET" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">General</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="RUC, razon social o correo"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">RUC</label>
                <input type="text" name="ruc" value="{{ request('ruc') }}" placeholder="Buscar RUC"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Razon Social</label>
                <input type="text" name="razon_social" value="{{ request('razon_social') }}" placeholder="Buscar empresa"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Estado</label>
                <select name="status" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Todos</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activas</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Suspendidas</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="min-w-24 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Buscar</button>
                <a href="{{ route('super-admin.companies.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="px-4 py-3 text-left font-medium text-gray-500">RUC</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Razon Social</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Correo</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Plan</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Docs</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">API Keys</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Fecha Registro</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                        @php
                            $totalDocs = $company->invoices_count + $company->boletas_count + $company->credit_notes_count + $company->debit_notes_count + $company->dispatch_guides_count;
                        @endphp
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $company->ruc }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $company->razon_social }}</div>
                                <div class="max-w-[220px] truncate text-xs text-gray-400">{{ $company->nombre_comercial ?? $company->direccion ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $company->email ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-600/20">
                                    {{ $company->plan->name ?? 'Sin plan' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ number_format($totalDocs) }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ number_format($company->api_keys_count) }}</td>
                            <td class="px-4 py-3">
                                {{-- El estado se ve aqui pero se cambia en Suscripciones: es el
                                     unico sitio donde se corta y se devuelve el acceso, para que
                                     no haya dos botones distintos haciendo lo mismo. --}}
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $company->activo ? 'bg-green-50 text-green-700 ring-1 ring-green-600/20' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20' }}">
                                    {{ $company->activo ? 'Activa' : 'Suspendida' }}
                                </span>
                                @if(! $company->activo)
                                    <span class="mt-0.5 block text-[11px] text-gray-500">
                                        {{ $company->suspendida_manualmente ? 'Suspendida por ti' : 'Suscripción vencida' }}
                                    </span>
                                @endif
                                @if($esSuperAdmin)
                                    <a href="{{ route('super-admin.subscriptions.index', ['search' => $company->ruc]) }}"
                                       class="mt-0.5 block text-[11px] text-blue-600 hover:underline">Gestionar acceso</a>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $company->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                {{-- Acciones como iconos macizos sobre pastilla de color. Cada boton
                                     lleva title (tooltip al pasar el raton) y un texto oculto para
                                     lectores de pantalla. --}}
                                <div class="flex items-center gap-1.5">
                                    <button type="button" title="Ver detalle"
                                            onclick="window.openAdminModal('{{ route('super-admin.companies.show', $company) }}?modal=1', 'Detalle de empresa')"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-inset ring-blue-100 transition hover:bg-blue-100 hover:text-blue-700">
                                        <span class="sr-only">Ver detalle</span>
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/>
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z"/>
                                        </svg>
                                    </button>

                                    <button type="button" title="Editar empresa"
                                            onclick="window.openAdminModal('{{ route('super-admin.companies.edit', $company) }}?modal=1', 'Editar empresa')"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-200 hover:text-slate-800">
                                        <span class="sr-only">Editar empresa</span>
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712z"/>
                                            <path d="M19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32L19.513 8.2z"/>
                                        </svg>
                                    </button>

                                    <form method="POST" action="{{ route('super-admin.companies.impersonate', $company) }}"
                                          onsubmit="return confirm('Vas a entrar al panel de {{ $company->razon_social }} como su administrador, con acceso completo. Todo lo que hagas queda registrado en auditoria. Continuar?')">
                                        @csrf
                                        <button type="submit" title="Entrar como soporte"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 ring-1 ring-inset ring-amber-200 transition hover:bg-amber-100 hover:text-amber-700">
                                            <span class="sr-only">Entrar como soporte</span>
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M16.5 3.75a1.5 1.5 0 011.5 1.5v13.5a1.5 1.5 0 01-1.5 1.5h-6a1.5 1.5 0 01-1.5-1.5V15a.75.75 0 00-1.5 0v3.75a3 3 0 003 3h6a3 3 0 003-3V5.25a3 3 0 00-3-3h-6a3 3 0 00-3 3V9A.75.75 0 009 9V5.25a1.5 1.5 0 011.5-1.5h6zM5.78 8.47a.75.75 0 00-1.06 0l-3 3a.75.75 0 000 1.06l3 3a.75.75 0 001.06-1.06l-1.72-1.72H15a.75.75 0 000-1.5H4.06l1.72-1.72a.75.75 0 000-1.06z"/>
                                            </svg>
                                        </button>
                                    </form>




                                    @if($esSuperAdmin)
                                    <form method="POST" action="{{ route('super-admin.companies.destroy', $company) }}" onsubmit="return confirm('Eliminar empresa {{ $company->razon_social }}? Esta accion no se puede deshacer.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Eliminar empresa"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 ring-1 ring-inset ring-red-200 transition hover:bg-red-100 hover:text-red-700">
                                            <span class="sr-only">Eliminar empresa</span>
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 013.878.512.75.75 0 11-.256 1.478l-.209-.035-1.005 13.07a3 3 0 01-2.991 2.77H8.084a3 3 0 01-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 01-.256-1.478A48.567 48.567 0 017.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 013.369 0c1.603.051 2.815 1.387 2.815 2.951zm-6.136-1.452a51.196 51.196 0 013.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 00-6 0v-.113c0-.794.609-1.428 1.364-1.452zm-.355 5.945a.75.75 0 10-1.5.058l.347 9a.75.75 0 101.499-.058l-.346-9zm5.48.058a.75.75 0 10-1.498-.058l-.347 9a.75.75 0 001.5.058l.345-9z"/>
                                            </svg>
                                        </button>
                                    </form>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-gray-500">No se encontraron empresas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-4 py-3">
            {{ $companies->links() }}
        </div>
    </div>

</div>
@endsection
