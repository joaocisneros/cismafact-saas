<?php

use App\Models\AuditLog;
use App\Support\AccionAuditada;

test('todas las acciones auditadas tienen descripcion y modulo concretos', function () {
    $uncovered = collect(app('router')->getRoutes()->getRoutes())
        ->filter(function ($route) {
            $name = (string) $route->getName();
            $mutates = array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== [];

            return $mutates
                && (str_starts_with($name, 'empresa.') || str_starts_with($name, 'super-admin.'))
                && in_array('audit.admin', $route->middleware(), true);
        })
        ->map(function ($route) {
            $action = in_array('DELETE', $route->methods(), true)
                ? 'delete'
                : (array_intersect($route->methods(), ['PUT', 'PATCH']) !== [] ? 'update' : 'create_or_action');
            $log = new AuditLog(['route_name' => $route->getName(), 'action' => $action]);

            return [
                'route' => $route->getName(),
                'description' => AccionAuditada::describir($log),
                'module' => AccionAuditada::modulo($log),
            ];
        })
        ->filter(fn ($item) => str_contains($item['description'], 'configuración del sistema')
            || str_starts_with($item['description'], 'Ejecutó una acción sobre')
            || $item['module'] === 'Panel')
        ->values()
        ->all();

    expect($uncovered)->toBe([]);
});

