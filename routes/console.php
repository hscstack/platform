<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 1. Clean up stale/unused resources and models first at 03:00
Schedule::command('resources:clean-unused-images')
    ->dailyAt('03:00')
    ->withoutOverlapping();

Schedule::command('model:prune')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// 2. Run backup 30 minutes later at 03:30 after deletions are complete
Schedule::command('backup:drive')
    ->dailyAt('03:30')
    ->withoutOverlapping();

// 3. Refresh sitemap at 04:00
Schedule::command('seo:sitemap')
    ->dailyAt('04:00')
    ->withoutOverlapping();
