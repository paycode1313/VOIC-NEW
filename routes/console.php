<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Privacy: guest exam data and recorded answers are removed after VOIC_RETENTION_DAYS.
// Hourly rather than nightly: the booth laptop is usually switched off at night, so a
// single 03:10 run would almost never happen. jalankan_voic.bat also prunes once at start.
Schedule::command('voic:prune')->hourly()->withoutOverlapping();
