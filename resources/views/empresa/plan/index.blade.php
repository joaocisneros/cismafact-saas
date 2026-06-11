@extends('layouts.app')

@section('title', 'Mi Plan')

@section('content')
@php
    $estadoLabels = ['trial' => 'Prueba', 'active' => 'Activa', 'suspended' => 'Suspendida', 'cancelled' => 'Cancelada', 'expired' => 'Vencida'];
    $estadoClass = match($subscription?->status) {
        'active' => 'bg-green-50 text-green-700',
        'trial' => 'bg-blue-50 text-blue-700',
        'suspended' => 'bg-amber-50 text-amber-700',
        'expired', 'cancelled' => 'bg-red-50 text-red-700',
        default => 'bg-gray-100 text-gray-600',
    };

    $barras = [
        ['Documentos este mes', $uso['documentos']],
        ['Llamadas API este mes', $uso['api']],
        ['Usuarios', $uso['usuarios']],
    ];
@endphp
<div class="space-y-6 max-w-4xl">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Mi Plan</h1>
        <p class="text-gray-500 mt-1">Tu plan actual y el consumo del mes.</p>
    </div>

    {{-- Plan actual --}}
    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-2xl p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-blue-100 text-sm">Plan actual</p>
                <h2 class="text-3xl font-bold mt-1">{{ $plan?->name ?? 'Sin plan' }}</h2>
                <p class="text-blue-100 mt-1">{{ $plan ? 'S/ ' . number_format((float) $plan->monthly_price, 2) . ' / mes' : '' }}</p>
            </div>
            <span class="rounded-full bg-white/20 px-3 py-1 text-sm font-medium">{{ $estadoLabels[$subscription?->status] ?? 'Sin suscripción' }}</span>
        </div>
        <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-blue-200">Inicio</p>
                <p class="font-medium">{{ $subscription?->starts_at?->format('d/m/Y') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-blue-200">Vencimiento</p>
                <p class="font-medium">{{ $subscription?->ends_at?->format('d/m/Y') ?? 'Sin vencimiento' }}</p>
            </div>
            <div>
                <p class="text-blue-200">Renovación</p>
                <p class="font-medium">{{ $subscription?->auto_renew ? 'Automática' : 'Manual' }}</p>
            </div>
        </div>
    </div>

    {{-- Aviso de vencimiento --}}
    @if($subscription && $subscription->ends_at && $subscription->ends_at->isFuture() && $subscription->ends_at->diffInDays() <= 7)
        <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
            ⚠️ Tu plan vence el {{ $subscription->ends_at->format('d/m/Y') }} ({{ $subscription->ends_at->diffForHumans() }}). Renueva para no interrumpir tu facturación.
        </div>
    @elseif($subscription && $subscription->status === 'expired')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
            ❌ Tu plan está vencido. Contacta a soporte para reactivarlo.
        </div>
    @endif

    {{-- Consumo --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6 space-y-5">
        <h2 class="font-semibold text-gray-800">Consumo del mes</h2>
        @foreach($barras as [$titulo, $m])
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-700">{{ $titulo }}</span>
                    <span class="text-gray-500">
                        @if($m['ilimitado'])
                            {{ number_format($m['usado']) }} <span class="text-gray-400">/ Ilimitado</span>
                        @else
                            {{ number_format($m['usado']) }} / {{ number_format($m['limite']) }}
                            <span class="text-gray-400">({{ number_format($m['disponible']) }} disponibles)</span>
                        @endif
                    </span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    @php
                        $barColor = $m['ilimitado'] ? 'bg-emerald-400' : ($m['porcentaje'] >= 90 ? 'bg-red-500' : ($m['porcentaje'] >= 70 ? 'bg-amber-500' : 'bg-blue-500'));
                    @endphp
                    <div class="h-2.5 rounded-full {{ $barColor }}"
                         style="width: {{ $m['ilimitado'] ? 8 : max(2, $m['porcentaje']) }}%"></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="text-sm text-gray-500">
        ¿Necesitas más capacidad? Escríbenos para subir de plan.
        <a href="https://wa.me/51921676408" target="_blank" class="text-blue-600 font-medium hover:text-blue-800">Contactar por WhatsApp</a>
    </div>
</div>
@endsection
