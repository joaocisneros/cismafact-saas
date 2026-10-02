{{-- PDF Ticket Client Info Component (Exact Design Match) --}}
{{-- Props: $client, $fecha_emision --}}

@php
    $tipoCliente = (string) ($client['tipo_documento'] ?? '');
    $etiquetaDocumento = in_array($tipoCliente, ['6', '06'], true)
        ? 'RUC'
        : (in_array($tipoCliente, ['1', '01'], true) ? 'DNI' : 'DOCUMENTO');
@endphp

<div class="client-section">
    {{-- Client Name --}}
    <div class="client-name">{{ strtoupper($client['razon_social'] ?? 'CAMILO SANCHEZ') }}</div>
    
    {{-- Separator --}}
    <div class="client-separator">---</div>
    
    {{-- Document Number --}}
    <div class="client-details">{{ $etiquetaDocumento }} {{ $client['numero_documento'] ?? 'N/A' }}</div>
    
    {{-- Date and Time --}}
    <div class="client-details">
        FECHA: {{ $fecha_emision ?? '06/03/2024' }} HORA: {{ now()->format('H:i:s A') }}
    </div>
</div>
