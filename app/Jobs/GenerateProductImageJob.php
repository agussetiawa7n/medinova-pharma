<?php

namespace App\Jobs;

use App\Models\AIProductQueue;
use App\Services\AIProductService;
use App\Services\ImageProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateProductImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [30, 60];
    public int $timeout = 300;

    public function __construct(public readonly int $queueItemId) {}

    public function handle(AIProductService $aiService, ImageProcessingService $imageService): void
    {
        set_time_limit(300);

        $item = AIProductQueue::findOrFail($this->queueItemId);

        if ($item->status === 'completed' || $item->status === 'saved') {
            return;
        }

        try {
            // Only call API if image generation is enabled
            if (\App\Models\Setting::get('ai.generate_images', '1') === '1') {
                $rawImage = $aiService->generateImage($item->product_name);
            } else {
                $rawImage = null;
            }

            // Use AI image, or placeholder fallback
            $placeholderUrl = "https://placehold.co/800x800/ffffff/333333.png?text=" . urlencode($item->product_name);
            $imageSource = $rawImage ?: $placeholderUrl;
            $finalPath = $imageService->processAndSave($imageSource, $item->product_name);

            // Falling back to the placeholder is not an error the workflow should
            // block on, but the admin needs to know it happened and why —
            // otherwise a grey box looks identical to a real product photo run.
            $placeholderReason = $rawImage ? null : $aiService->lastImageError();

            // Verify the file was actually created
            $fullPath = storage_path('app/public/' . $finalPath);
            if (!file_exists($fullPath)) {
                throw new \Exception("Image file not created at: {$fullPath}");
            }

            $item->update([
                'image_path'       => $finalPath,
                'image_raw_url'    => filter_var($imageSource, FILTER_VALIDATE_URL) ? $imageSource : null,
                'image_model_used' => $rawImage ? \App\Models\Setting::get('ai.image_model', 'openai/gpt-5-image') : 'placeholder',
                'status'           => 'completed',
                'error_message'    => $placeholderReason
                    ? 'Placeholder used — ' . Str::limit($placeholderReason, 250)
                    : null,
            ]);

            Log::info("AI image completed #{$this->queueItemId}: {$item->product_name}", ['path' => $finalPath]);
        } catch (\Exception $e) {
            Log::error("AI image failed #{$this->queueItemId}: " . $e->getMessage());

            // Still mark completed so workflow is not blocked, but record the error
            $item->update([
                'status'        => 'completed',
                'error_message' => 'Image: ' . Str::limit($e->getMessage(), 250),
            ]);
        }
    }
}