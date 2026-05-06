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

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(public readonly int $queueItemId) {}

    public function uniqueId(): string
    {
        return 'ai_product_' . $this->queueItemId;
    }

    public function handle(AIProductService $aiService, ImageProcessingService $imageService): void
    {
        $item = AIProductQueue::findOrFail($this->queueItemId);
        $item->update(['status' => 'generating']);

        try {
            $details = $aiService->generateProductDetails($item->product_name);
            $rawImage = $aiService->generateImage($item->product_name);
            $finalImagePath = null;

            if ($rawImage) {
                $finalImagePath = $imageService->processAndSave($rawImage, $item->product_name);
            }

            $item->update([
                'generated_data'   => $details,
                'image_path'       => $finalImagePath,
                'image_raw_url'    => filter_var($rawImage, FILTER_VALIDATE_URL) ? $rawImage : null,
                'text_model_used'  => \App\Models\Setting::get('ai.text_model', 'openai/gpt-4o-mini'),
                'image_model_used' => \App\Models\Setting::get('ai.image_model', 'openai/dall-e-3'),
                'status'           => 'completed',
                'error_message'    => null,
            ]);

        } catch (\Exception $e) {
            Log::error("AI generation failed for ID {$this->queueItemId}: " . $e->getMessage());

            $item->update([
                'status'        => $this->attempts() >= $this->tries ? 'failed' : 'pending',
                'error_message' => $e->getMessage(),
                'retry_count'   => $item->retry_count + 1,
            ]);

            throw $e;
        }
    }
}
