<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 1. Clean up stale/unused resources and models first at 00:00 (12:00 AM)
Schedule::command('resources:clean-unused-images')
    ->dailyAt('00:00')
    ->withoutOverlapping();

Schedule::command('model:prune')
    ->dailyAt('00:00')
    ->withoutOverlapping();

// 2. Run backup 30 minutes later at 00:30 after deletions are complete
Schedule::command('backup:drive')
    ->dailyAt('00:30')
    ->withoutOverlapping();

// 3. Refresh sitemap at 01:00
Schedule::command('seo:sitemap')
    ->dailyAt('01:00')
    ->withoutOverlapping();
