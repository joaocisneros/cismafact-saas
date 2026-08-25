@extends('layouts.app')

@section('title', 'Sucursales')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Sucursales</h2>
            <p class="mt-1 text-sm text-gray-500">
                Tus establecimientos declarados ante SUNAT. Cada uno lleva sus propias series.
            </p>
        </div>

        <button type="button"
                onclick="window.openAdminModal('{{ route('empresa.branches.create') }}', 'Nueva sucursal')"
                class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700">
            Nueva sucursal
        </button>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3">
            <p class="mb-1 text-sm font-semibold">Revisa estos datos:</p>
            <ul class="list-inside list-disc space-y-0.5 text-sm">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-5 py-3">Código</th>
                    <th class="px-5 py-3">Sucursal</th>
                    <th class="px-5 py-3">Ubicación</th>
                    <th class="px-5 py-3">Series</th>
                    <th class="px-5 py-3">Estado</th>
                    <th class="px-5 py-3">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($branches as $branch)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-4">
                            <span class="rounded bg-slate-100 px-2 py-1 font-mono text-xs font-semibold text-slate-700">{{ $branch->codigo }}</span>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-medium text-gray-900">{{ $branch->nombre }}</p>
                            <p class="max-w-[260px] truncate text-xs text-gray-500">{{ $branch->direccion }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600">
                            {{ $branch->distrito }}
                            <span class="block text-xs text-gray-400">{{ $branch->provincia }}, {{ $branch->departamento }}</span>
                        </td>
                        <td class="px-5 py-4">
                            @if($branch->correlatives->isEmpty())
                                <a href="{{ route('empresa.correlatives.index') }}"
                                   class="text-xs font-medium text-amber-700 underline">Sin series — añadir</a>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach($branch->correlatives as $serie)
                                        <span class="rounded bg-slate-50 px-1.5 py-0.5 font-mono text-xs text-slate-600 ring-1 ring-inset ring-slate-200"
                                              title="Va por el número {{ $serie->correlativo_actual }}">{{ $serie->serie }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @if($branch->activo)
                                <span class="rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-200">Activa</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Inactiva</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-1.5">
                                <x-icon-action icon="editar" label="Editar sucursal" color="blue" type="button"
                                               onclick="window.openAdminModal('{{ route('empresa.branches.edit', $branch) }}', 'Editar {{ $branch->nombre }}')" />
                                <form method="POST" action="{{ route('empresa.branches.toggle', $branch) }}">
                                    @csrf
                                    <x-icon-action :icon="$branch->activo ? 'suspender' : 'activar'"
                                                   :label="$branch->activo ? 'Desactivar sucursal' : 'Activar sucursal'"
                                                   :color="$branch->activo ? 'amber' : 'emerald'" />
                                </form>
                                <form method="POST" action="{{ route('empresa.branches.destroy', $branch) }}"
                                      onsubmit="return confirm('¿Eliminar la sucursal {{ $branch->nombre }}?')">
                                    @csrf @method('DELETE')
                                    <x-icon-action icon="eliminar" label="Eliminar sucursal" color="red" />
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-gray-500">
                            Todavía no tienes sucursales.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
        <p class="text-sm text-blue-900">
            <strong>Cada serie pertenece a una sola sucursal.</strong>
            Si dos sedes usaran la misma serie, emitirían el mismo número con tu RUC y SUNAT lo rechazaría.
            Las series se gestionan en <a href="{{ route('empresa.correlatives.index') }}" class="font-medium underline">Correlativos</a>.
        </p>
    </div>
</div>
@endsection
