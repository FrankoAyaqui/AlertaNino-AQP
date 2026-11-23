<?php

use Illuminate\Support\Facades\Schedule;

// Lectura automática de las fuentes oficiales (requiere "php artisan schedule:work"
// o una tarea programada que ejecute "php artisan schedule:run" cada minuto).
Schedule::command('alerta:sincronizar')->everyThirtyMinutes()->withoutOverlapping();
