<?php

namespace App\Jobs;

use App\Models\AIProductQueue;
use App\Services\AIProductService;
use App\Services\ImageProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateProductFromAI implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [30, 60];
    public int $timeout = 120;

    public function __construct(public readonly int $queueItemId) {}

    public function uniqueId(): string
    {
        return 'ai_product_' . $this->queueItemId;
    }

    public function handle(AIProductService $aiService, ImageProcessingService $imageService): void
    {
        set_time_limit(120);

        $item = AIProductQueue::findOrFail($this->queueItemId);

        // Skip if already completed
        if (in_array($item->status, ['completed', 'saved', 'skipped'])) {
            return;
        }

        $item->update(['status' => 'generating']);

        // ── STEP 1: Generate TEXT (fast, 2-8s) ──
        try {
            $details = $aiService->generateProductDetails($item->product_name);

            $item->update([
                'generated_data'  => $details,
                'text_model_used' => \App\Models\Setting::get('ai.text_model', 'openai/gpt-5-mini'),
                'error_message'   => null,
            ]);
        } catch (\Exception $e) {
            Log::error("AI text failed for #{$this->queueItemId}: " . $e->getMessage());
            $item->update([
                'status'        => 'failed',
                'error_message' => 'Text: ' . $e->getMessage(),
            ]);
            return;
        }

        // ── STEP 2: Generate IMAGE (slower, skip if disabled) ──
        if ((bool) \App\Models\Setting::get('ai.generate_images', 'true')) {
            try {
                $rawImage = $aiService->generateImage($item->product_name);
                if ($rawImage) {
                    $finalImagePath = $imageService->processAndSave($rawImage, $item->product_name);
                    $item->update([
                        'image_path'       => $finalImagePath,
                        'image_raw_url'    => filter_var($rawImage, FILTER_VALIDATE_URL) ? $rawImage : null,
                        'image_model_used' => \App\Models\Setting::get('ai.image_model', 'openai/gpt-5-image-mini'),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("AI image failed for #{$this->queueItemId}: " . $e->getMessage());
                // Don't fail the whole item — text is still good
                $item->update(['error_message' => 'Image: ' . $e->getMessage()]);
            }
        }

        // ── Mark complete ──
        $item->update(['status' => 'completed']);
    }
}