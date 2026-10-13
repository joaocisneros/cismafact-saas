<?php

use App\Models\Company;
use App\Models\ConsultaLlave;
use App\Models\User;
use App\Support\AuditContext;

test('captura correo servicio entorno y tipo de una llave de consultas', function () {
    $actor = new User(['name' => 'Administrador', 'email' => 'admin@cismafact.com']);
    $key = new ConsultaLlave(['entorno' => 'sandbox']);

    $context = AuditContext::capture(
        'super-admin.consultas.llaves.actualizar',
        ['titular_email' => 'cliente@empresa.com', 'servicios' => ['ruc', 'dni']],
        $key,
        null,
        $actor,
    );

    expect($context)->toMatchArray([
        'actor_user_id' => $actor->id,
        'actor_name' => 'Administrador',
        'actor_email' => 'admin@cismafact.com',
        'target_email' => 'cliente@empresa.com',
        'service_context' => 'api_ruc_dni',
        'environment_context' => 'sandbox',
        'event_type' => 'RUC Y DNI',
    ]);
});

test('reconoce el correo del cliente y el tipo SUNAT dentro de una emision', function () {
    $context = AuditContext::capture(
        'empresa.invoices.store',
        [
            'tipo_documento' => '01',
            'cliente' => ['email' => 'compras@cliente.pe'],
        ],
        null,
        null,
        null,
    );

    expect($context)
        ->target_email->toBe('compras@cliente.pe')
        ->target_name->toBeNull()
        ->service_context->toBe('facturacion')
        ->event_type->toBe('Factura');
});

test('conserva el nombre del registro afectado', function () {
    $client = (object) ['id' => 9, 'nombre' => 'Comercial Andina SAC'];

    $context = AuditContext::capture(
        'empresa.clients.update',
        [],
        $client,
        null,
        null,
    );

    expect($context['target_name'])->toBe('Comercial Andina SAC');
});

test('conserva el ambiente de facturacion que tenia la empresa al actuar', function () {
    $company = new Company(['modo_produccion' => true]);

    $context = AuditContext::capture(
        'empresa.facturas.store',
        [],
        null,
        $company,
        null,
    );

    expect($context['service_context'])->toBe('facturacion')
        ->and($context['environment_context'])->toBe('produccion')
        ->and($context['event_type'])->toBe('Factura');
});
