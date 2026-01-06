<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily sales report to run every day at 6 PM
Schedule::command('report:daily-sales')
    ->dailyAt('18:00')
    ->timezone('UTC');

// Check for low stock every 10 minutes (batched, efficient)
Schedule::command('stock:check-low')
    ->everyTenMinutes()
    ->withoutOverlapping();

// Expire carts every hour (carts older than 24 hours)
Schedule::command('carts:expire', ['--hours' => 24])
    ->hourly()
    ->withoutOverlapping();
