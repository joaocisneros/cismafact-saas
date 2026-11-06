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

test('describe en palabras las peticiones a la API de facturación', function (string $method, string $path, string $expected) {
    $log = new AuditLog(['route_name' => 'api.facturacion', 'method' => $method, 'path' => $path]);

    expect(AccionAuditada::describir($log))->toBe($expected);
})->with([
    ['POST', 'api/boletas', 'Emitió una boleta por API'],
    ['GET', 'api/facturas', 'Consultó sus facturas por API'],
    ['GET', 'api/facturas/12', 'Consultó una factura por API'],
    ['GET', 'api/v1/boletas/26/download-pdf', 'Descargó el PDF de una boleta por API'],
    ['GET', 'api/notas-credito/4/download-cdr', 'Descargó el CDR de una nota de crédito por API'],
    ['GET', 'api/panel/estado-sunat', 'Consultó los indicadores de su panel por API'],
    ['GET', 'api/consultas/ruc/20100070970', 'Consultó un RUC por API'],
    ['GET', 'api/algo-nuevo', 'Usó la API: GET /api/algo-nuevo'],
]);

test('distingue inicios de sesión y consultas de RUC y DNI', function () {
    $entrada = (new AuditLog(['route_name' => 'login.correcto', 'response_status' => 200]))->forceFill(['fuente' => 'acceso']);
    $fallo = (new AuditLog(['route_name' => 'login.fallido', 'response_status' => 401]))->forceFill(['fuente' => 'acceso']);
    $consulta = (new AuditLog([
        'route_name' => 'api.consultas', 'event_type' => 'DNI',
        'response_status' => 404, 'description' => 'Ese DNI no figura en RENIEC.',
    ]))->forceFill(['fuente' => 'api_consultas']);

    expect(AccionAuditada::describir($entrada))->toBe('Inició sesión en el panel')
        ->and(AccionAuditada::modulo($entrada))->toBe('Inicio de sesión')
        ->and(AccionAuditada::resultado($fallo)['texto'])->toBe('Rechazado')
        ->and(AccionAuditada::describir($consulta))->toBe('Consultó un DNI por la API')
        ->and(AccionAuditada::porApi($consulta))->toBeTrue()
        ->and(AccionAuditada::resultado($consulta))->toMatchArray([
            'texto' => 'Sin resultado',
            'detalle' => 'Ese DNI no figura en RENIEC.',
        ]);
});

test('reconoce los programas que llaman a la API', function () {
    $postman = new AuditLog(['user_agent' => 'PostmanRuntime/7.39.0']);

    expect(AccionAuditada::dispositivo($postman))->toBe([
        'tipo' => 'Integración',
        'navegador' => 'Postman',
        'sistema' => 'llamada directa',
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

