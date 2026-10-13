<?php

use App\Models\AuditLog;
use App\Support\AccionAuditada;

test('describe con precision los cambios de metas y comisiones', function (string $route, string $expected) {
    $log = new AuditLog([
        'route_name' => $route,
        'action' => str_contains($route, 'restablecer') ? 'delete' : 'create_or_action',
    ]);

    expect(AccionAuditada::describir($log))->toBe($expected);
})->with([
    [
        'empresa.rendimiento.configuracion',
        'Guardó la configuración de metas y comisiones',
    ],
    [
        'empresa.rendimiento.comisiones.restablecer',
        'Eliminó la configuración de metas y comisiones',
    ],
    [
        'empresa.rendimiento.comisiones.historial.limpiar',
        'Eliminó el historial de comisiones',
    ],
]);

test('usa el tipo de objeto cuando aparece una ruta nueva', function () {
    $log = new AuditLog([
        'route_name' => 'empresa.operaciones.archivar',
        'action' => 'delete',
        'subject_type' => 'App\\Models\\Client',
    ]);

    expect(AccionAuditada::describir($log))->toBe('Eliminó un cliente');
});

test('distingue al responsable de la cuenta utilizada durante soporte', function () {
    $actor = new App\Models\User(['name' => 'Administrador SUNAT', 'email' => 'admin@cismafact.com']);
    $cuenta = new App\Models\User(['name' => 'Dueño Empresa', 'email' => 'dueno@empresa.com']);
    $log = new AuditLog([
        'actor_name' => $actor->name,
        'actor_email' => $actor->email,
        'description' => '[SOPORTE: Administrador SUNAT] empresa.company.update',
    ]);
    $log->setRelation('actor', $actor);
    $log->setRelation('user', $cuenta);

    expect(AccionAuditada::nombreActor($log))->toBe('Administrador SUNAT')
        ->and(AccionAuditada::correoActor($log))->toBe('admin@cismafact.com')
        ->and(AccionAuditada::cuentaUtilizada($log))->toBe([
            'nombre' => 'Dueño Empresa',
            'correo' => 'dueno@empresa.com',
        ]);
});

test('identifica dispositivo navegador y sistema desde el user agent', function () {
    $movil = new AuditLog(['user_agent' => 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/124.0 Mobile Safari/537.36']);
    $escritorio = new AuditLog(['user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36 Edg/124.0']);

    expect(AccionAuditada::dispositivo($movil))->toBe([
        'tipo' => 'Móvil',
        'navegador' => 'Chrome',
        'sistema' => 'Android',
    ])->and(AccionAuditada::dispositivo($escritorio))->toBe([
        'tipo' => 'Computadora',
        'navegador' => 'Edge',
        'sistema' => 'Windows',
    ]);
});

