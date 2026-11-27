<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Genera los pedidos de las suscripciones activas con entrega vencida.
Schedule::command('subscriptions:generate-orders')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->description('Genera los pedidos de suscripciones cuya proxima entrega vence');

// Reclama clientes que cumplieron los dias de inactividad configurados.
Schedule::command('marketing:run-retention')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->description('Envia el mensaje de recuperacion a clientes inactivos');

// Membresias Hogar: emite facturas, avisa mora y suspende a los 15 dias.
Schedule::command('membership:daily')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->description('Emite facturas Hogar, avisa mora y suspende impagas');

// Visitas al sitio: completa el pais de las filas pendientes por lotes.
Schedule::command('visits:geolocate')
    ->hourly()
    ->withoutOverlapping()
    ->description('Geolocaliza por lotes las visitas sin pais registrado');
