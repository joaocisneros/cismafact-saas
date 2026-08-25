@extends('layouts.app')

@section('title', 'Mi Plan')

@section('content')
@php
    $estadoLabels = ['trial' => 'Prueba', 'active' => 'Activa', 'suspended' => 'Suspendida', 'cancelled' => 'Cancelada', 'expired' => 'Vencida'];

    $barras = [
        ['Comprobantes emitidos', $uso['documentos'], 'este mes'],
        ['Llamadas a la API', $uso['api'], 'este mes'],
        ['Usuarios', $uso['usuarios'], 'de tu equipo'],
    ];

    $diasParaVencer = $subscription?->ends_at
        ? (int) now()->startOfDay()->diffInDays($subscription->ends_at->startOfDay(), false)
        : null;

    // El aviso se arma una vez y se pinta abajo, para no repetir tres bloques
    // casi iguales con distinto color.
    $aviso = null;
    if ($subscription?->status === 'expired' || ($diasParaVencer !== null && $diasParaVencer < 0)) {
        $aviso = ['tono' => 'red', 'texto' => 'Tu plan está vencido. Mientras siga así no podrás emitir comprobantes.'];
    } elseif ($diasParaVencer !== null && $diasParaVencer <= 7 && ! $subscription?->auto_renew) {
        $aviso = ['tono' => 'amber', 'texto' => $diasParaVencer === 0
            ? 'Tu plan vence hoy. A partir de mañana no podrás emitir comprobantes.'
            : "Tu plan vence el {$subscription->ends_at->format('d/m/Y')}, en {$diasParaVencer} " . ($diasParaVencer === 1 ? 'día' : 'días') . '.'];
    }

    // Se abre en el modal del panel, como el resto: mandar al usuario a otra
    // pantalla para escribir dos lineas rompe el hilo de lo que estaba viendo.
    $enlaceRenovar = route('empresa.support.create', [
        'subject' => 'Renovación de mi plan',
        'motivo' => 'renovacion',
        'priority' => 'high',
        'message' => 'Hola, quiero renovar o cambiar mi plan. Indíquenme el importe y la forma de pago.',
        'modal' => 1,
    ]);
@endphp

<div class="mx-auto max-w-3xl space-y-5">

    <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900">Mi Plan</h1>
        <p class="mt-1 text-sm text-gray-500">Tu plan actual y lo que llevas consumido este mes.</p>
    </div>

    {{-- Tarjeta del plan. Fondo oscuro plano en vez del degradado azul: destaca
         igual, no compite con los avisos de color y envejece mejor. --}}
    <div class="rounded-2xl bg-gray-900 p-6 text-white">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400">Plan actual</p>
                <h2 class="mt-1 text-3xl font-bold">{{ $plan?->name ?? 'Sin plan' }}</h2>
                @if($plan)
                    <p class="mt-1 text-gray-300">
                        {{ (float) $plan->monthly_price > 0 ? 'S/ ' . number_format((float) $plan->monthly_price, 2) . ' al mes' : 'Gratuito' }}
                    </p>
                @endif
            </div>
            <span class="rounded-full bg-white/10 px-3 py-1 text-sm font-medium">
                {{ $estadoLabels[$subscription?->status] ?? 'Sin suscripción' }}
            </span>
        </div>

        <div class="mt-6 grid gap-4 border-t border-white/10 pt-5 text-sm sm:grid-cols-3">
            <div>
                <p class="text-gray-400">Inicio</p>
                <p class="mt-0.5 font-medium">{{ $subscription?->starts_at?->format('d/m/Y') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400">Vencimiento</p>
                <p class="mt-0.5 font-medium">{{ $subscription?->ends_at?->format('d/m/Y') ?? 'Sin vencimiento' }}</p>
            </div>
            <div>
                <p class="text-gray-400">Renovación</p>
                <p class="mt-0.5 font-medium">{{ $subscription?->auto_renew ? 'Automática' : 'Manual' }}</p>
            </div>
        </div>
    </div>

    @if($aviso)
        <div class="rounded-lg border px-4 py-3 text-sm {{ $aviso['tono'] === 'red' ? 'border-red-200 bg-red-50 text-red-800' : 'border-amber-200 bg-amber-50 text-amber-900' }}">
            {{ $aviso['texto'] }}
            <button type="button" onclick="window.openAdminModal('{{ $enlaceRenovar }}', 'Renovar o cambiar de plan')"
                    class="font-medium underline">Renovar o cambiar de plan</button>
        </div>
    @endif

    {{-- Consumo --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="font-semibold text-gray-900">Consumo</h2>
        <p class="mt-1 text-sm text-gray-500">Los contadores mensuales se reinician el día 1.</p>

        <div class="mt-5 space-y-5">
            @foreach($barras as [$titulo, $m, $periodo])
                @php
                    $tono = $m['ilimitado']
                        ? 'bg-gray-300'
                        : ($m['porcentaje'] >= 90 ? 'bg-red-500' : ($m['porcentaje'] >= 70 ? 'bg-amber-500' : 'bg-blue-500'));
                @endphp
                <div>
                    <div class="mb-1.5 flex flex-wrap items-baseline justify-between gap-2">
                        <span class="text-sm font-medium text-gray-800">
                            {{ $titulo }} <span class="font-normal text-gray-400">{{ $periodo }}</span>
                        </span>
                        <span class="text-sm text-gray-600">
                            @if($m['ilimitado'])
                                {{ number_format($m['usado']) }} <span class="text-gray-400">· sin límite</span>
                            @else
                                <span class="font-semibold text-gray-900">{{ number_format($m['usado']) }}</span>
                                <span class="text-gray-400">de {{ number_format($m['limite']) }}</span>
                            @endif
                        </span>
                    </div>

                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                        <div class="h-2 rounded-full {{ $tono }}"
                             style="width: {{ $m['ilimitado'] ? 100 : max(2, min(100, $m['porcentaje'])) }}%"></div>
                    </div>

                    @if(! $m['ilimitado'])
                        <p class="mt-1 text-xs {{ $m['porcentaje'] >= 90 ? 'text-red-600' : 'text-gray-500' }}">
                            @if($m['disponible'] <= 0)
                                Alcanzaste el límite de tu plan.
                            @else
                                Te quedan {{ number_format($m['disponible']) }}.
                            @endif
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    @php
        // Dos caminos distintos: extender lo que ya tienes, o pasar a otro plan.
        $enlaceCambiar = route('empresa.support.create', [
            'subject' => 'Cambio de plan',
            'motivo' => 'cambio_plan',
            'priority' => 'medium',
            'message' => 'Hola, quiero cambiar de plan. Indíquenme las opciones y el importe.',
            'modal' => 1,
        ]);
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-5 text-center">
        <p class="text-sm text-gray-600">¿Quieres seguir con este plan o pasar a otro?</p>
        <div class="mt-3 flex flex-wrap justify-center gap-2">
            <button type="button" onclick="window.openAdminModal('{{ $enlaceRenovar }}', 'Renovar mi plan')"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                Renovar este plan
            </button>
            <button type="button" onclick="window.openAdminModal('{{ $enlaceCambiar }}', 'Cambiar de plan')"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                Cambiar de plan
            </button>
        </div>
    </div>
</div>
@endsection
