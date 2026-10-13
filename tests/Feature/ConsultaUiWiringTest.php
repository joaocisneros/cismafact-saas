<?php

test('las acciones administrativas de consultas tienen rutas registradas', function () {
    $routes = collect(app('router')->getRoutes())->map->getName()->filter();

    expect($routes)->toContain(
        'super-admin.consultas.llaves.guardar',
        'super-admin.consultas.llaves.actualizar',
        'super-admin.consultas.llaves.alternar',
        'super-admin.consultas.llaves.regenerar',
        'super-admin.consultas.llaves.renovar',
        'super-admin.consultas.llaves.secreto',
        'super-admin.consultas.llaves.borrar',
        'super-admin.consultas.llaves.limpiar-vencidas',
        'super-admin.consultas.planes.guardar',
        'super-admin.consultas.planes.actualizar',
        'super-admin.consultas.planes.borrar',
        'super-admin.consultas.acceso.crear',
        'super-admin.consultas.acceso.clave',
        'super-admin.consultas.acceso.quitar',
        'super-admin.consultas.acceso.entrar',
    );
});

test('produccion y sandbox abren el mismo modal seguro de renovacion', function () {
    $apis = file_get_contents(resource_path('views/super-admin/consultas/tabs/apis.blade.php'));
    $sandbox = file_get_contents(resource_path('views/super-admin/consultas/tabs/sandbox.blade.php'));
    $modal = file_get_contents(resource_path('views/super-admin/consultas/tabs/_renovar.blade.php'));

    expect($apis)->toContain("\$dispatch('abrir-renovacion'")
        ->and($sandbox)->toContain("\$dispatch('abrir-renovacion'")
        ->and($modal)->toContain('@abrir-renovacion.window="abrir($event.detail)"')
        ->and($modal)->toContain('<template x-if="renovacion">')
        ->and($modal)->toContain("/renovar'");
});
