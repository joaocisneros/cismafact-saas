<?php

use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

test('la aplicacion y las fechas relativas usan español', function () {
    expect(app()->getLocale())->toBe('es')
        ->and(Carbon::now()->subWeek()->diffForHumans())->toContain('hace 1 semana');
});
