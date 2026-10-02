@extends('layouts.app')

@section('title', 'Ventas por usuario')

@section('content')
<div class="space-y-5" x-data="{ configOpen: {{ $errors->any() ? 'true' : 'false' }}, enabled: {{ old('active', $configComision->active) ? 'true' : 'false' }}, mode: @js(old('mode', $configComision->mode)), appliesAll: {{ old('applies_to_all', $configComision->applies_to_all ? 1 : 0) ? 'true' : 'false' }} }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-slate-900">Ventas por usuario</h1>
            <p class="mt-1 text-sm text-slate-500">Controla cuánto vende y cuántos comprobantes emite cada usuario de tu empresa.</p>
        </div>
        <form method="GET" class="w-full rounded-xl border border-slate-200 bg-white p-3 shadow-sm" style="max-width: 520px; flex: 0 1 520px;">
            <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
                </svg>
                Periodo de consulta
            </div>
            <div class="flex flex-wrap items-end gap-2">
                <label class="block min-w-36 flex-1">
                    <span class="mb-1 block text-xs font-medium text-slate-600">Desde</span>
                    <input type="date" name="desde" value="{{ $desde->toDateString() }}" class="block h-10 w-full rounded-lg border-slate-300 bg-slate-50 px-3 text-sm text-slate-800 focus:border-blue-500 focus:bg-white focus:ring-blue-500">
                </label>
                <span class="mb-3 hidden text-slate-300 sm:block">→</span>
                <label class="block min-w-36 flex-1">
                    <span class="mb-1 block text-xs font-medium text-slate-600">Hasta</span>
                    <input type="date" name="hasta" value="{{ $hasta->toDateString() }}" class="block h-10 w-full rounded-lg border-slate-300 bg-slate-50 px-3 text-sm text-slate-800 focus:border-blue-500 focus:bg-white focus:ring-blue-500">
                </label>
                <button class="inline-flex h-10 items-center justify-center rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 max-sm:w-full">
                    Aplicar
                </button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <x-stat-card title="Total vendido" :value="'S/ '.number_format($totalVentas, 2)" subtitle="Suma de facturas y boletas válidas" color="blue">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Comprobantes" :value="number_format($totalDocumentos)" subtitle="Todos los tipos emitidos en el periodo" color="purple">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Usuario con más ventas" :value="$lider?->usuario?->name ?? 'Sin actividad'" :subtitle="$lider ? 'S/ '.number_format($lider->ventas, 2).' vendidos' : 'No hay comprobantes en el periodo'" color="green">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8zm8 2l2 2 4-4"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-semibold text-slate-900">Resumen por usuario</h2><p class="text-xs text-slate-500">Los rechazados se muestran para control, pero no suman en ventas ni en el ticket promedio.</p></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Usuario</th><th class="px-3 py-3 text-center">Facturas</th><th class="px-3 py-3 text-center">Boletas</th><th class="px-3 py-3 text-center" title="Notas de crédito">N. crédito</th><th class="px-3 py-3 text-center" title="Notas de débito">N. débito</th><th class="px-3 py-3 text-center">Guías</th><th class="px-4 py-3 text-right"><span class="block">Total vendido</span><span class="normal-case text-[10px] font-normal text-slate-400">Suma válida</span></th><th class="px-4 py-3 text-right"><span class="block">Promedio por venta</span><span class="normal-case text-[10px] font-normal text-slate-400">Total ÷ comprobantes válidos</span></th><th class="px-4 py-3">Estado SUNAT</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($filas as $fila)
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-5 py-3"><p class="font-medium text-slate-900">{{ $fila->usuario->name }}</p><p class="text-xs text-slate-400">{{ $fila->usuario->email }} · {{ $fila->usuario->role?->display_name }}</p></td>
                        <td class="px-3 py-3 text-center tabular-nums">{{ $fila->facturas }}</td><td class="px-3 py-3 text-center tabular-nums">{{ $fila->boletas }}</td>
                        <td class="px-3 py-3 text-center tabular-nums">{{ $fila->notas_credito }}</td><td class="px-3 py-3 text-center tabular-nums">{{ $fila->notas_debito }}</td><td class="px-3 py-3 text-center tabular-nums">{{ $fila->guias }}</td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-900">S/ {{ number_format($fila->ventas, 2) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-slate-600">S/ {{ number_format($fila->ticket_promedio, 2) }}</td>
                        <td class="px-4 py-3"><span class="text-emerald-700">{{ $fila->aceptados }} aceptados</span><span class="mx-1 text-slate-300">·</span><span class="text-amber-700">{{ $fila->pendientes }} pendientes</span>@if($fila->rechazados)<span class="mx-1 text-slate-300">·</span><span class="text-red-700">{{ $fila->rechazados }} rechazados</span>@endif</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="font-semibold text-slate-900">Metas y comisiones</h2>
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $configComision->active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $configComision->active ? 'Activo' : 'Desactivado' }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-slate-500">Cálculo estimado del periodo. No modifica comprobantes ni registra pagos automáticamente.</p>
            </div>
            <button type="button" @click="configOpen = true" class="inline-flex h-10 items-center gap-2 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4v2m0-6V4m12 14a2 2 0 100-4m0 4v2m0-6V4"/></svg>
                {{ $configComision->exists ? 'Editar configuración' : 'Configurar' }}
            </button>
        </div>

        @if(!$configComision->active)
            <div class="flex flex-col items-center px-6 py-10 text-center">
                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M5 21h14"/></svg>
                </div>
                <p class="font-semibold text-slate-900">Define incentivos para tu equipo</p>
                <p class="mt-1 max-w-xl text-sm text-slate-500">Puedes usar una meta, una comisión porcentual o ambas. La función solo comienza cuando tú la activas.</p>
                <button type="button" @click="configOpen = true" class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Activar y configurar</button>
            </div>
        @else
            @php
                $modalidades = ['goal' => 'Bono por meta', 'commission' => 'Comisión por ventas', 'both' => 'Comisión + bono'];
            @endphp
            <div class="grid gap-3 border-b border-slate-100 bg-slate-50/70 px-5 py-3 text-sm sm:grid-cols-4">
                <div><span class="block text-xs text-slate-400">Modalidad</span><strong class="text-slate-800">{{ $modalidades[$configComision->mode] ?? '—' }}</strong></div>
                <div><span class="block text-xs text-slate-400">Meta mensual</span><strong class="text-slate-800">S/ {{ number_format($configComision->monthly_goal, 2) }}</strong></div>
                <div><span class="block text-xs text-slate-400">Comisión</span><strong class="text-slate-800">{{ number_format($configComision->commission_percentage, 2) }}%</strong></div>
                <div><span class="block text-xs text-slate-400">Bono por meta</span><strong class="text-slate-800">S/ {{ number_format($configComision->goal_bonus, 2) }}</strong></div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-white text-left text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Usuario</th><th class="px-4 py-3 text-right">Venta válida</th><th class="min-w-44 px-4 py-3">Avance de meta</th><th class="px-4 py-3 text-right">Comisión</th><th class="px-4 py-3 text-right">Bono</th><th class="px-4 py-3 text-right">Total estimado</th><th class="px-5 py-3 text-right">Control</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($comisiones as $fila)
                            <tr>
                                <td class="px-5 py-3"><p class="font-medium text-slate-900">{{ $fila->usuario->name }}</p><p class="text-xs text-slate-400">{{ $fila->usuario->email }}</p></td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums">S/ {{ number_format($fila->ventas, 2) }}</td>
                                <td class="px-4 py-3">
                                    @if((float)$configComision->monthly_goal > 0)
                                        <div class="mb-1 flex justify-between text-xs"><span>{{ $fila->avance }}%</span><span class="{{ $fila->meta_cumplida ? 'font-medium text-emerald-600' : 'text-slate-400' }}">{{ $fila->meta_cumplida ? 'Meta alcanzada' : 'En progreso' }}</span></div>
                                        <div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $fila->meta_cumplida ? 'bg-emerald-500' : 'bg-blue-500' }}" style="width: {{ $fila->avance }}%"></div></div>
                                    @else
                                        <span class="text-xs text-slate-400">Sin meta configurada</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">S/ {{ number_format($fila->comision, 2) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">S/ {{ number_format($fila->bono, 2) }}</td>
                                <td class="px-4 py-3 text-right font-bold tabular-nums text-slate-900">S/ {{ number_format($fila->total, 2) }}</td>
                                <td class="px-5 py-3 text-right">
                                    @if($fila->resultado?->status === 'paid')
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Pagada</span>
                                    @else
                                        <form method="POST" action="{{ route('empresa.rendimiento.comisiones.actualizar', $fila->usuario) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="period" value="{{ $desde->copy()->startOfMonth()->toDateString() }}">
                                            <input type="hidden" name="status" value="{{ $fila->resultado?->status === 'approved' ? 'paid' : 'approved' }}">
                                            <button class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $fila->resultado?->status === 'approved' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'border border-slate-300 text-slate-700 hover:bg-slate-50' }}">{{ $fila->resultado?->status === 'approved' ? 'Marcar pagada' : 'Aprobar' }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-500">No hay empleados incluidos en esta configuración.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between px-5 py-4"><div><h2 class="font-semibold text-slate-900">Historial de comisiones</h2><p class="mt-0.5 text-xs text-slate-500">Registro de comisiones aprobadas y pagadas.</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-500">{{ $historialComisiones->count() }} registros</span></div>
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 bg-slate-50/70 px-5 py-2">
                <p class="text-xs text-slate-500">Conserva los resultados aprobados y pagados de cada mes.</p>
                <div class="flex items-center gap-2">
                    @if($historialComisiones->isNotEmpty())
                        <a href="{{ route('empresa.rendimiento.comisiones.exportar') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/></svg>Exportar CSV</a>
                        <form method="POST" action="{{ route('empresa.rendimiento.comisiones.historial.limpiar') }}" onsubmit="return confirm('¿Eliminar definitivamente todo el historial de comisiones? Esta acción no se puede deshacer.')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="confirmacion" value="LIMPIAR_HISTORIAL">
                            <button class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Limpiar historial</button>
                        </form>
                    @else
                        <span class="text-xs text-slate-400">Todavía no hay datos para exportar.</span>
                    @endif
                </div>
            </div>
            @if($historialComisiones->isNotEmpty())
            <div class="overflow-x-auto border-t border-slate-200">
                <table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Periodo</th><th class="px-4 py-3">Usuario</th><th class="px-4 py-3 text-right">Venta</th><th class="px-4 py-3 text-right">Comisión</th><th class="px-4 py-3 text-right">Bono</th><th class="px-4 py-3 text-right">Total</th><th class="px-5 py-3">Estado</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">@foreach($historialComisiones as $registro)<tr><td class="px-5 py-3">{{ $registro->period->translatedFormat('F Y') }}</td><td class="px-4 py-3 font-medium">{{ $registro->user?->name ?? 'Usuario eliminado' }}</td><td class="px-4 py-3 text-right">S/ {{ number_format($registro->net_sales, 2) }}</td><td class="px-4 py-3 text-right">S/ {{ number_format($registro->commission_amount, 2) }}</td><td class="px-4 py-3 text-right">S/ {{ number_format($registro->bonus_amount, 2) }}</td><td class="px-4 py-3 text-right font-semibold">S/ {{ number_format($registro->total_amount, 2) }}</td><td class="px-5 py-3"><span class="rounded-full px-2 py-1 text-xs font-medium {{ $registro->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">{{ $registro->status === 'paid' ? 'Pagada' : 'Aprobada' }}</span></td></tr>@endforeach</tbody>
                </table>
            </div>
            @else
                <div class="border-t border-slate-100 px-5 py-8 text-center"><p class="text-sm font-medium text-slate-600">Aún no hay comisiones guardadas</p><p class="mt-1 text-xs text-slate-400">Cuando apruebes una comisión aparecerá aquí y permanecerá aunque cambies la configuración.</p></div>
            @endif
        </section>

    <div x-cloak x-show="configOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @keydown.escape.window="configOpen = false">
        <div @click.outside="configOpen = false" class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <form method="POST" action="{{ route('empresa.rendimiento.configuracion') }}">
                @csrf
                <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4"><div><h3 class="text-lg font-bold text-slate-900">Metas y comisiones</h3><p class="mt-0.5 text-sm text-slate-500">Configura una regla sencilla para tu equipo.</p></div><button type="button" @click="configOpen = false" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Cerrar">✕</button></div>
                <div class="space-y-4 px-6 py-4">
                    @if($errors->any())<div class="rounded-lg bg-red-50 p-3 text-sm text-red-700">Revisa los campos marcados antes de guardar.</div>@endif
                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3"><span><strong class="block text-sm text-slate-900">Usar metas y comisiones</strong><span class="text-xs text-slate-500" x-text="enabled ? 'Activado para esta empresa' : 'Desactivado; no se calcularán incentivos'"></span></span><input type="hidden" name="active" :value="enabled ? '1' : '0'"><input type="checkbox" x-model="enabled" class="peer sr-only"><span class="relative h-6 w-11 shrink-0 rounded-full bg-slate-300 transition peer-checked:bg-blue-600 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition after:content-[''] peer-checked:after:translate-x-5"></span></label>
                    @php
                        $opcionesComision = [
                            'goal' => ['Bono por meta', 'Paga un monto fijo solo cuando el vendedor alcanza el objetivo.'],
                            'commission' => ['Comisión por ventas', 'Paga un porcentaje de cada venta válida, sin exigir una meta.'],
                            'both' => ['Comisión + bono', 'Paga el porcentaje y agrega un bono al alcanzar la meta.'],
                        ];
                    @endphp
                    <div>
                        <p class="mb-1 text-sm font-semibold text-slate-800">¿Cómo quieres premiar a tus vendedores?</p>
                        <p class="mb-3 text-xs text-slate-500">Selecciona una opción. Puedes cambiarla después.</p>
                        <div class="grid grid-cols-1 gap-1 rounded-xl bg-slate-100 p-1 sm:grid-cols-3">
                            @foreach($opcionesComision as $valor => [$texto, $descripcion])
                                <label class="cursor-pointer rounded-lg px-3 py-2.5 text-center transition" :class="mode === '{{ $valor }}' ? 'bg-white text-blue-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-white/60 hover:text-slate-700'">
                                    <input type="radio" name="mode" value="{{ $valor }}" x-model="mode" class="sr-only">
                                    <strong class="text-sm"><span x-show="mode === '{{ $valor }}'" class="mr-1 text-blue-600">✓</span>{{ $texto }}</strong>
                                </label>
                            @endforeach
                        </div>
                        <div class="mt-2 flex gap-3 rounded-xl border border-blue-100 bg-blue-50/60 px-3 py-2 text-xs leading-relaxed text-slate-600">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">i</span>
                            <template x-if="mode === 'goal'"><span><strong class="block text-slate-800">Bono al cumplir el objetivo</strong>Ejemplo: meta S/ 1,000 y premio S/ 100. Si vende S/ 1,000 o más, recibe S/ 100.</span></template>
                            <template x-if="mode === 'commission'"><span><strong class="block text-slate-800">Porcentaje de cada venta válida</strong>Ejemplo: con 2%, si vende S/ 1,000 recibe S/ 20.</span></template>
                            <template x-if="mode === 'both'"><span><strong class="block text-slate-800">Porcentaje más premio</strong>Ejemplo: recibe el 2% de sus ventas y, al alcanzar la meta, también el bono configurado.</span></template>
                        </div>
                    </div>
                    <div class="grid items-end gap-3" :class="mode === 'both' ? 'sm:grid-cols-3' : 'sm:grid-cols-2'">
                        <label x-show="mode === 'goal' || mode === 'both'" class="block"><span class="mb-1 block whitespace-nowrap text-sm font-medium text-slate-700">Meta mensual</span><div class="flex h-10 overflow-hidden rounded-lg border border-slate-300 bg-white focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500"><span class="flex items-center border-r border-slate-200 bg-slate-50 px-3 text-sm text-slate-500">S/</span><input type="number" step="0.01" min="0" name="monthly_goal" value="{{ old('monthly_goal', $configComision->monthly_goal ?? 0) }}" class="min-w-0 flex-1 border-0 px-3 text-sm focus:ring-0"></div></label>
                        <label x-show="mode === 'commission' || mode === 'both'" class="block"><span class="mb-1 block whitespace-nowrap text-sm font-medium text-slate-700">Comisión por venta</span><div class="flex h-10 overflow-hidden rounded-lg border border-slate-300 bg-white focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500"><input type="number" step="0.01" min="0" max="100" name="commission_percentage" value="{{ old('commission_percentage', $configComision->commission_percentage ?? 0) }}" class="min-w-0 flex-1 border-0 px-3 text-sm focus:ring-0"><span class="flex items-center border-l border-slate-200 bg-slate-50 px-3 text-sm text-slate-500">%</span></div></label>
                        <label x-show="mode === 'goal' || mode === 'both'" class="block"><span class="mb-1 block whitespace-nowrap text-sm font-medium text-slate-700">Bono por meta</span><div class="flex h-10 overflow-hidden rounded-lg border border-slate-300 bg-white focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500"><span class="flex items-center border-r border-slate-200 bg-slate-50 px-3 text-sm text-slate-500">S/</span><input type="number" step="0.01" min="0" name="goal_bonus" value="{{ old('goal_bonus', $configComision->goal_bonus ?? 0) }}" class="min-w-0 flex-1 border-0 px-3 text-sm focus:ring-0"></div></label>
                    </div>
                    <div><div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-semibold text-slate-800">¿A quién se aplica?</p><div class="inline-flex rounded-lg bg-slate-100 p-1 text-sm"><label class="cursor-pointer rounded-md px-3 py-1.5" :class="appliesAll ? 'bg-white font-medium text-slate-900 shadow-sm' : 'text-slate-500'"><input type="radio" name="applies_to_all" value="1" x-model.boolean="appliesAll" class="sr-only">Todos</label><label class="cursor-pointer rounded-md px-3 py-1.5" :class="!appliesAll ? 'bg-white font-medium text-slate-900 shadow-sm' : 'text-slate-500'"><input type="radio" name="applies_to_all" value="0" x-model.boolean="appliesAll" class="sr-only">Elegir empleados</label></div></div><div x-show="!appliesAll" class="mt-2 grid gap-x-4 gap-y-2 rounded-xl border border-slate-200 bg-slate-50/50 p-3 sm:grid-cols-3">@foreach($empleados as $empleado)<label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="selected_user_ids[]" value="{{ $empleado->id }}" @checked(in_array($empleado->id, old('selected_user_ids', $configComision->selected_user_ids ?? []))) class="rounded border-slate-300 text-blue-600"> {{ $empleado->name }}</label>@endforeach</div></div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <div class="flex items-center gap-3">
                        @if($configComision->exists || $historialComisiones->isNotEmpty())
                            <button type="submit" form="restablecer-comisiones" onclick="return confirm('¿Borrar la configuración actual? El historial de comisiones se conservará.')" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 text-xs font-semibold text-red-600 shadow-sm transition hover:border-red-300 hover:bg-red-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/></svg>
                                Borrar configuración
                            </button>
                        @endif
                        <p class="text-xs text-slate-500">Se aplicará al guardar.</p>
                    </div>
                    <div class="flex gap-2"><button type="button" @click="configOpen = false" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Cancelar</button><button class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">Guardar configuración</button></div>
                </div>
            </form>

            <form id="restablecer-comisiones" method="POST" action="{{ route('empresa.rendimiento.comisiones.restablecer') }}" class="hidden">
                @csrf
                @method('DELETE')
                <input type="hidden" name="confirmacion" value="RESTABLECER">
            </form>

        </div>
    </div>
</div>
@endsection
