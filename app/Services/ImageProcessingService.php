<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

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
        $ext     = 'png';
        $rawPath = str_replace('\\', '/', storage_path("app/temp/{$slug}_raw.png"));

        $tempDir = str_replace('\\', '/', storage_path('app/temp'));
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        if (filter_var($imageSource, FILTER_VALIDATE_URL)) {
            $rawContent = Http::timeout(60)->get($imageSource)->body();
            file_put_contents($rawPath, $rawContent);
        } else {
            $rawPath = $imageSource;
            // Detect SVG files from file extension or content
            if (str_ends_with($rawPath, '.svg')) {
                $ext = 'svg';
            } elseif (file_exists($rawPath)) {
                $head = file_get_contents($rawPath, false, null, 0, 100);
                if (str_starts_with(trim($head), '<svg')) {
                    $ext = 'svg';
                }
            }
        }

        $outputDir = str_replace('\\', '/', storage_path("app/public/products/{$slug}"));
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        if ($ext === 'svg') {
            // SVG: copy directly, no resize needed
            $svgPath = "{$outputDir}/{$slug}.svg";
            copy($rawPath, $svgPath);
            if (file_exists($rawPath) && str_contains(str_replace('\\', '/', $rawPath), '/temp/')) {
                unlink($rawPath);
            }
            return "products/{$slug}/{$slug}.svg";
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
        $manager = ImageManager::usingDriver(GdDriver::class);

        $manager->decode($rawPath)
            ->contain($pixels, $pixels, '#ffffff')
            ->save($outputPath, quality: $this->getQuality($sizeName));
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
