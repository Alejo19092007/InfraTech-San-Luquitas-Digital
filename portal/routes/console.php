<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tareas programadas (las ejecuta el contenedor "scheduler" con `schedule:work`; zona horaria de Bogotá).
Schedule::command('tareas:recordatorios')->dailyAt('06:30')->withoutOverlapping()->onOneServer();
Schedule::command('codigos:limpiar')->dailyAt('03:00')->withoutOverlapping();
