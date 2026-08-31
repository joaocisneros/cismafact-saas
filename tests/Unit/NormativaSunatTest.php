<?php

use App\Http\Requests\Empresa\StoreNotaDebitoRequest;
use App\Support\NormativaSunat;
use Illuminate\Support\Carbon;

/**
 * La RS 000143-2026/SUNAT aplazo la RS 000048-2026 al 01/01/2027. Estas reglas
 * no deben adelantarse: antes de esa fecha SUNAT acepta el formato anterior, y
 * rechazar aqui lo que SUNAT admite bloquea al emisor sin motivo.
 */
afterEach(fn () => Carbon::setTestNow());

test('antes del 01/01/2027 el codigo de producto admite el formato anterior', function () {
    Carbon::setTestNow('2026-12-31');

    expect(NormativaSunat::rs048Vigente())->toBeFalse()
        ->and(NormativaSunat::reglaCodigoProducto())->toBe('nullable|string|max:50');
});

test('desde el 01/01/2027 el codigo de producto exige 8 digitos', function () {
    Carbon::setTestNow('2027-01-01');

    expect(NormativaSunat::rs048Vigente())->toBeTrue()
        ->and(NormativaSunat::reglaCodigoProducto())->toBe('nullable|digits:8');
});

test('el motivo 13 de nota de debito no existe antes de su vigencia', function () {
    Carbon::setTestNow('2026-12-31');

    expect(NormativaSunat::motivosNotaDebito())->not->toContain('13')
        ->and(StoreNotaDebitoRequest::motivos())->not->toHaveKey('13')
        ->and(NormativaSunat::penalidadesInafectas())->toBeFalse();
});

test('el motivo 13 y su regla de inafecto entran el 01/01/2027', function () {
    Carbon::setTestNow('2027-01-01');

    expect(NormativaSunat::motivosNotaDebito())->toContain('13')
        ->and(StoreNotaDebitoRequest::motivos())->toHaveKey('13')
        ->and(NormativaSunat::penalidadesInafectas())->toBeTrue();
});

test('los emisores de DAE/DAEE se designan recien el 01/04/2027', function () {
    Carbon::setTestNow('2027-03-31');
    expect(NormativaSunat::daeVigente())->toBeFalse();

    Carbon::setTestNow('2027-04-01');
    expect(NormativaSunat::daeVigente())->toBeTrue();
});
