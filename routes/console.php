<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── AI Queue Auto-Processing ──
//
// Hostinger hPanel → Advanced → Cron Jobs, type "PHP", every minute:
//   domains/moccasin-chimpanzee-720084.hostingersite.com/artisan schedule:run
// (hPanel prepends "/usr/bin/php /home/u933134862/" itself. Creating this as a
//  "Custom" job without the PHP binary makes cron try to execute artisan
//  directly, which fails with "Permission denied" and never runs.)
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

