<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Обновляем каталог раз в неделю, чтобы сайт работал на локальной базе.
// Для исполнения расписания используйте системный cron с schedule:run
// каждую минуту либо `php artisan schedule:work`.
Schedule::command('chemistry:sync')
    ->weeklyOn(1, '04:17')
    ->withoutOverlapping();
