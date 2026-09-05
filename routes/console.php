<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Google finishes settling the previous day's figures a few hours after
 * midnight, so the archive is written well after the day has closed. The run
 * re-syncs a rolling window rather than only yesterday, so a missed night
 * heals itself instead of leaving a permanent gap.
 */
Schedule::command('analytics:sync')
    ->dailyAt('04:00')
    ->timezone(config('analytics.timezone'))
    ->withoutOverlapping()
    ->onOneServer();
