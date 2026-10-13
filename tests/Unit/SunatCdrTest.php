<?php

use App\Services\GreenterService;
use App\Support\SunatCdr;
use Greenter\Ws\Services\SunatEndpoints;

function fakeCdr(string $code, string $description = 'Respuesta SUNAT'): object
{
    return new class($code, $description) {
        public function __construct(private string $code, private string $description) {}
        public function getId(): string { return 'R-20123456789-01-F001-1'; }
        public function getCode(): string { return $this->code; }
        public function getDescription(): string { return $this->description; }
        public function getNotes(): array { return ['observacion']; }
    };
}

test('solo el codigo cero del CDR se considera aceptado', function () {
    expect(SunatCdr::isAccepted(fakeCdr('0')))->toBeTrue()
        ->and(SunatCdr::isAccepted(fakeCdr('2335', 'Documento rechazado')))->toBeFalse()
        ->and(SunatCdr::isAccepted(null))->toBeFalse();
});

test('conserva codigo descripcion y observaciones del CDR', function () {
    expect(SunatCdr::payload(fakeCdr('0')))->toMatchArray([
        'code' => '0',
        'description' => 'Respuesta SUNAT',
        'notes' => ['observacion'],
    ]);
});

test('retenciones eligen el endpoint correspondiente al ambiente', function () {
    expect(GreenterService::retentionEndpointFor(false))->toBe(SunatEndpoints::RETENCION_BETA)
        ->and(GreenterService::retentionEndpointFor(true))->toBe(SunatEndpoints::RETENCION_PRODUCCION);
});
