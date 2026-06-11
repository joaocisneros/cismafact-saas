@props(['status' => 'pending'])

@php
    $statuses = [
        'ACEPTADO' => ['bg-green-100 text-green-700', 'Aceptado'],
        'accepted' => ['bg-green-100 text-green-700', 'Aceptado'],
        'RECHAZADO' => ['bg-red-100 text-red-700', 'Rechazado'],
        'rejected' => ['bg-red-100 text-red-700', 'Rechazado'],
        'PENDIENTE' => ['bg-yellow-100 text-yellow-700', 'Pendiente'],
        'pending' => ['bg-yellow-100 text-yellow-700', 'Pendiente'],
        'ENVIADO' => ['bg-blue-100 text-blue-700', 'Enviado'],
        'sent' => ['bg-blue-100 text-blue-700', 'Enviado'],
        'ANULADO' => ['bg-gray-100 text-gray-700', 'Anulado'],
        'active' => ['bg-green-100 text-green-700', 'Activo'],
        'inactive' => ['bg-red-100 text-red-700', 'Inactivo'],
    ];

    [$classes, $label] = $statuses[$status] ?? ['bg-gray-100 text-gray-700', $status];
@endphp

<span class="px-2 py-1 {{ $classes }} rounded-full text-xs font-medium">
    {{ $label }}
</span>
