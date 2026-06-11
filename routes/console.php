<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('subscriptions:sync', function () {
    $processed = app(\App\Services\SubscriptionStatusService::class)->synchronizeDueSubscriptions();
    $this->info("Suscripciones sincronizadas: {$processed}");
})->purpose('Renueva o vence suscripciones según su fecha de finalización');

Schedule::command('subscriptions:sync')
    ->dailyAt('00:10')
    ->withoutOverlapping();

Schedule::command('certificados:notificar-vencimiento')
    ->dailyAt('07:00')
    ->withoutOverlapping();
