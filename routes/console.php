<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── AI Queue Auto-Processing ──
// Runs every 5 minutes — works on shared hosting with a single cron entry.
// In Hostinger hPanel, add ONE cron job:
// * * * * * /usr/local/bin/php8.2 /home/u123456789/domains/yourdomain.com/public_html/artisan schedule:run >> /dev/null 2>&1
Schedule::command('ai:process-queue --max=3')->everyFiveMinutes()
    ->withoutOverlapping(10)  // Skip if previous run still going (10 min max)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/ai-queue-cron.log'));

