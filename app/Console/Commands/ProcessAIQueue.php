<?php

namespace App\Console\Commands;

use App\Jobs\GenerateProductImageJob;
use App\Jobs\GenerateProductTextJob;
use App\Models\AIProductQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessAIQueue extends Command
{
    protected $signature   = 'ai:process-queue
                                {--max=3 : Maximum number of items to process in one run}
                                {--text-only : Only process text generation jobs}
                                {--image-only : Only process image generation jobs}';

    protected $description = 'Process pending AI product generation jobs. Safe for shared hosting cron.';

    public function handle(): int
    {
        $max = (int) $this->option('max');
        $processed = 0;

        $this->info("AI Queue Processor started. Max items: {$max}");

        // ── STEP 1: Process pending text jobs ──
        if (!$this->option('image-only')) {
            $textItems = AIProductQueue::where('status', 'pending')
                ->orderBy('created_at')
                ->take($max)
                ->get();

            foreach ($textItems as $item) {
                if ($processed >= $max) break;

                $this->line("→ Text: [{$item->product_name}]");

                try {
                    dispatch_sync(new GenerateProductTextJob($item->id));
                    $processed++;
                    $this->info("  ✅ Text done: {$item->product_name}");
                } catch (\Exception $e) {
                    $this->error("  ❌ Text failed: " . $e->getMessage());
                    Log::error("ai:process-queue text failed [{$item->product_name}]: " . $e->getMessage());
                }
            }
        }

        // ── STEP 2: Process text_generated → image jobs ──
        if (!$this->option('text-only')) {
            $imageItems = AIProductQueue::where('status', 'text_generated')
                ->orderBy('updated_at')
                ->take($max)
                ->get();

            foreach ($imageItems as $item) {
                if ($processed >= $max) break;

                $this->line("→ Image: [{$item->product_name}]");

                try {
                    dispatch_sync(new GenerateProductImageJob($item->id));
                    $processed++;
                    $this->info("  ✅ Image done: {$item->product_name}");
                } catch (\Exception $e) {
                    $this->error("  ❌ Image failed: " . $e->getMessage());
                    Log::error("ai:process-queue image failed [{$item->product_name}]: " . $e->getMessage());
                }
            }
        }

        $pending = AIProductQueue::whereIn('status', ['pending', 'text_generated'])->count();
        $this->info("Done. Processed: {$processed}. Still pending: {$pending}");

        return Command::SUCCESS;
    }
}
