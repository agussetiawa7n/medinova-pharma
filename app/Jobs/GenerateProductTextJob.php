<?php

namespace App\Jobs;

use App\Models\AIProductQueue;
use App\Services\AIProductService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateProductTextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // NOTE: $tries/$backoff are Queue worker settings and never apply here —
    // this plan has no worker, so every dispatch is dispatch_sync(). Retries
    // are owned by AIQueueProcessor::MAX_ATTEMPTS. $timeout is kept only to
    // document the intended ceiling; set_time_limit() below does the work.
    // YMYL content is large; DeepSeek generation can take 45–60s. Keep this well
    // above the service HTTP timeout (90s) so a real queue worker doesn't kill the
    // job mid-generation.
    public int $timeout = 120;

    public function __construct(public readonly int $queueItemId) {}

    public function handle(AIProductService $aiService): void
    {
        set_time_limit(120);

        $item = AIProductQueue::findOrFail($this->queueItemId);

        if (in_array($item->status, ['completed', 'saved', 'skipped'])) {
            return;
        }

        $item->update(['status' => 'generating', 'error_message' => null]);

        try {
            if ($item->type === 'category') {
                $details = $aiService->generateCategoryDetails($item->product_name);

                $item->update([
                    'generated_data'  => $details,
                    'text_model_used' => \App\Models\Setting::get('ai.text_model', 'google/gemini-2.0-flash-001'),
                    'status'          => 'completed',
                ]);
            } else {
                // A category pinned on the queue row is passed straight into
                // the prompt, so the model writes for that category instead of
                // choosing its own. Resolved by id — the stored name could have
                // been edited between enqueue and generation.
                $forcedCategory = $item->category_id
                    ? \App\Models\Category::whereKey($item->category_id)->value('name')
                    : null;

                $details = $aiService->generateProductDetails($item->product_name, $forcedCategory);

                $item->update([
                    'generated_data'  => $details,
                    'text_model_used' => \App\Models\Setting::get('ai.text_model', 'google/gemini-2.0-flash-001'),
                    'status'          => 'text_generated',
                ]);

                // Do NOT dispatch the image job here. There is no queue worker
                // on this hosting plan (proc_open/exec are disabled), so a
                // dispatched job would just pile up unprocessed in the `jobs`
                // table. AIQueueProcessor picks the item up on its next step.
                // Compare with '1' like AIProductService does — the setting is
                // a string, and (bool) "false" is true.
                if (\App\Models\Setting::get('ai.generate_images', '1') !== '1') {
                    $item->update(['status' => 'completed']);
                }
            }

        } catch (\Exception $e) {
            Log::error("AI text failed #{$this->queueItemId}: " . $e->getMessage());

            // Leave status/retry bookkeeping to AIQueueProcessor so attempts are
            // counted in exactly one place. Just surface the error and rethrow.
            $item->update(['error_message' => \Illuminate\Support\Str::limit($e->getMessage(), 450)]);

            throw $e;
        }
    }
}