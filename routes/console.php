<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── AI Queue Auto-Processing ──
//
// Hostinger hPanel → Advanced → Cron Jobs, type "Custom", every minute
// (* * * * *). This exact command is live and verified:
//
//   /opt/alt/php84/usr/bin/php /home/u933134862/domains/moccasin-chimpanzee-720084.hostingersite.com/artisan schedule:run
//
// Two traps, both hit on this account before it worked:
//   1. Omitting the PHP binary (a "Custom" job of just ".../artisan schedule:run")
//      makes cron exec artisan directly → "Permission denied", never runs.
//   2. hPanel's "PHP" job type hardcodes /usr/bin/php, which is 8.2 here even
//      though the site runs 8.4 → "Composer dependencies require PHP >= 8.4.0".
//      The versioned CLI binary must be spelled out, hence the Custom job.
//
// NO ->runInBackground() here. That option shells out through proc_open, and
// this plan disables proc_open/exec/shell_exec (hPanel → PHP Configuration →
// disableFunctions), so a backgrounded task would throw instead of running.
//
// One item per tick keeps every run far inside maxExecutionTime (300s) — a
// DeepSeek text call is 45-90s and an image 2-3 min, so batching times out.
Schedule::command('ai:process-queue --max=1')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->appendOutputTo(storage_path('logs/ai-queue-cron.log'));

