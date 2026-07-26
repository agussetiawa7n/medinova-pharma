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
            $response = Http::timeout(60)->get($imageSource);

            // Without this check an error page (403/404 HTML, a JSON error body)
            // was written straight into {slug}_raw.png and only blew up later as
            // Intervention's opaque "File contains unsupported image format".
            if (!$response->successful()) {
                throw new \RuntimeException(
                    "Image download failed: HTTP {$response->status()} from {$imageSource}"
                );
            }

            file_put_contents($rawPath, $response->body());
        } else {
            $rawPath = $imageSource;
        }

        // Sniff whatever we ended up with, no matter where it came from. The
        // detection used to run only for local files, so a downloaded SVG — the
        // placeholder service returns SVG unless an extension is given — reached
        // the GD driver, which cannot decode it.
        $ext = $this->detectFormat($rawPath, $imageSource);

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

    /**
     * Work out what we actually downloaded by looking at the bytes, and fail
     * with a message that names the problem instead of letting the image
     * library report a generic decode error three retries later.
     */
    private function detectFormat(string $path, string $source): string
    {
        if (!file_exists($path) || filesize($path) === 0) {
            throw new \RuntimeException("Image source produced an empty file: {$source}");
        }

        $head = (string) file_get_contents($path, false, null, 0, 512);

        if (str_ends_with(strtolower($path), '.svg')
            || str_contains($head, '<svg')
            || str_starts_with(ltrim($head), '<?xml')) {
            return 'svg';
        }

        // getimagesize() returns false for anything GD cannot read, which is
        // precisely the set of inputs that used to reach ->decode() and throw.
        if (@getimagesize($path) === false) {
            $preview = trim(preg_replace('/\s+/', ' ', substr($head, 0, 120)));
            throw new \RuntimeException(
                "Downloaded file is not a readable image (starts with: \"{$preview}\") from {$source}"
            );
        }

        return 'png';
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
            $rawUrl = "https://placehold.co/1024x1024/ffffff/333333.png?text=" . urlencode($queueItem->product_name);
        }
        $finalPath = $this->processAndSave($rawUrl, $queueItem->product_name);
        $queueItem->update(['image_path' => $finalPath, 'image_raw_url' => $rawUrl]);
        return $finalPath;
    }
}
