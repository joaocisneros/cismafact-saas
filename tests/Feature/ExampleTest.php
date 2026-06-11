<?php

test('the application returns a successful response', function () {
    $routes = collect(app('router')->getRoutes())->map->uri()->all();

    expect($routes)->toContain('api/system/info');
});
