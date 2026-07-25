<?php

namespace App\Console\Commands;

use App\Services\AIQueueProcessor;
use Illuminate\Console\Command;

class ProcessAIQueue extends Command
{
    protected $signature = 'ai:process-queue
                                {--max=1 : How many steps to run in this invocation}
                                {--type= : Restrict to "product" or "category"}';

    protected $description = 'Advance the AI generation queue by one step per item. Safe for shared-hosting cron.';

    /**
     * A "step" is one text generation OR one image generation for a single
     * item — never a whole product end to end. The cron fires every minute, so
     * throughput comes from frequency, not from batching inside one run.
     * Batching is what used to push runs past maxExecutionTime and strand rows.
     */
    public function handle(AIQueueProcessor $processor): int
    {
        $max  = max(1, (int) $this->option('max'));
        $type = $this->option('type') ?: null;

        $reclaimed = $processor->reclaimStale();
        if ($reclaimed > 0) {
            $this->warn("Reclaimed {$reclaimed} stale item(s) from a previous run.");
        }

        $done = 0;

        for ($i = 0; $i < $max; $i++) {
            $item = $processor->step($type);

            if (!$item) {
                break; // nothing left to claim, or another worker holds it
            }

            $done++;
            $this->line("→ [{$item->status}] {$item->product_name}");
        }

        $this->info(sprintf(
            'Steps run: %d. Still outstanding: %d.',
            $done,
            $processor->pendingCount($type)
        ));

        return self::SUCCESS;
    }
}
