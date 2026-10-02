{{-- PDF Ticket Footer Component (Exact Design Match) --}}
{{-- Props: $qr_code, $hash, $document, $tipo_documento_nombre --}}

@php
    $urlCisma = rtrim(config('services.cisma_fact.public_url'), '/');
    $urlVerificacionSunat = config('services.cisma_fact.sunat_verification_url');
@endphp

{{-- QR Code Section --}}
@if(isset($qr_code) && !empty($qr_code))
    <div class="qr-section">
        <div class="qr-code">
            <img src="{{ $qr_code }}" alt="QR Code">
        </div>
    </div>
@endif

{{-- Footer Text --}}
<div class="footer-text">
    Representación impresa de la {{ strtoupper($tipo_documento_nombre ?? 'BOLETA DE VENTA ELECTRONICA') }}.<br>
    Verifique su validez en<br>
    <a href="{{ $urlVerificacionSunat }}">Consulta de CPE - SUNAT</a>
</div>

{{-- Hash Code --}}
@if(isset($hash) && !empty($hash))
    <div class="footer-auth">
        <strong>Hash:</strong> {{ $hash }}
    </div>
@endif

{{-- Footer URL --}}
<div class="footer-url">
    <a href="{{ $urlCisma }}">{{ preg_replace('#^https?://#', '', $urlCisma) }}</a>
</div>

{{-- Powered By --}}
<div class="powered-by">
    Powered by Cisma Fact
</div>
