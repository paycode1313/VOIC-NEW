<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Privacy: guest exam data and recorded answers are removed after VOIC_RETENTION_DAYS.
Schedule::command('voic:prune')->dailyAt('03:10');
