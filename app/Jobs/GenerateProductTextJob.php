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

    public int $tries = 2;
    public array $backoff = [15, 30];
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
                $details = $aiService->generateProductDetails($item->product_name);

                $item->update([
                    'generated_data'  => $details,
                    'text_model_used' => \App\Models\Setting::get('ai.text_model', 'google/gemini-2.0-flash-001'),
                    'status'          => 'text_generated',
                ]);

                // Dispatch image job
                if ((bool) \App\Models\Setting::get('ai.generate_images', 'true')) {
                    dispatch(new GenerateProductImageJob($this->queueItemId));
                } else {
                    $item->update(['status' => 'completed']);
                }
            }

        } catch (\Exception $e) {
            Log::error("AI text failed #{$this->queueItemId}: " . $e->getMessage());

            $item->update([
                'status'        => $this->attempts() >= $this->tries ? 'failed' : 'pending',
                'error_message' => $e->getMessage(),
            ]);

            if ($this->attempts() >= $this->tries) {
                return;
            }
            throw $e;
        }
    }
}