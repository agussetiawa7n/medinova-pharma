<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ImageProcessingService
{
    const SIZES = [
        'large'     => 800,
        'medium'    => 400,
        'thumbnail' => 200,
    ];

    public function processAndSave(string $imageSource, string $productName): string
    {
        $slug    = Str::slug($productName);
        $rawPath = storage_path("app/temp/{$slug}_raw.png");

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if (filter_var($imageSource, FILTER_VALIDATE_URL)) {
            $rawContent = Http::timeout(60)->get($imageSource)->body();
            file_put_contents($rawPath, $rawContent);
        } else {
            $rawPath = $imageSource;
        }

        $outputDir = storage_path("app/public/products/{$slug}");
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        foreach (self::SIZES as $sizeName => $pixels) {
            $this->saveSize($rawPath, $outputDir, $slug, $sizeName, $pixels);
        }

        if (file_exists($rawPath) && str_contains($rawPath, '/temp/')) {
            unlink($rawPath);
        }

        return "products/{$slug}/{$slug}_large.webp";
    }

    private function saveSize(string $rawPath, string $outputDir, string $slug, string $sizeName, int $pixels): void
    {
        $outputPath = "{$outputDir}/{$slug}_{$sizeName}.webp";

        Image::read($rawPath)
            ->resize($pixels, $pixels, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->resizeCanvas($pixels, $pixels, 'center', false, '#ffffff')
            ->toWebp($this->getQuality($sizeName))
            ->save($outputPath);
    }

    private function getQuality(string $sizeName): int
    {
        return match ($sizeName) {
            'large'     => 90,
            'medium'    => 82,
            'thumbnail' => 75,
            default     => 82,
        };
    }

    public function regenerate(int $queueId): string
    {
        $queueItem = \App\Models\AIProductQueue::findOrFail($queueId);
        $aiService = app(AIProductService::class);
        $rawUrl    = $aiService->generateImage($queueItem->product_name);
        if (!$rawUrl) {
            $rawUrl = "https://placehold.co/1024x1024/ffffff/333333?text=" . urlencode($queueItem->product_name);
        }
        $finalPath = $this->processAndSave($rawUrl, $queueItem->product_name);
        $queueItem->update(['image_path' => $finalPath, 'image_raw_url' => $rawUrl]);
        return $finalPath;
    }
}
