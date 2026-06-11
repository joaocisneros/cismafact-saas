<?php

test('system info endpoint responds successfully', function () {
    $routes = collect(app('router')->getRoutes())->map->uri()->all();

    expect($routes)->toContain('api/system/info');
});

test('invoice routes are registered', function () {
    $routes = collect(app('router')->getRoutes())->map->uri()->all();

    expect($routes)->toContain('api/v1/invoices');
    expect($routes)->toContain('api/v1/invoices/{id}/send-sunat');
});
