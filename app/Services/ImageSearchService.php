<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImageSearchService
{
    /**
     * Trusted pharmaceutical image sources only.
     * Never use random Google Images blindly.
     */
    private const TRUSTED_DOMAINS = [
        'indiamart.com',       // ← FIRST PRIORITY: Best for Indian pharma
        '1mg.com',
        'pharmeasy.in',
        'netmeds.com',
        'apollopharmacy.in',
        'apollo247.com',
        'flipkart.com',
        'amazon.in',
        'medplusmart.com',
        'truemeds.in',
        'healthkart.com',
        'tatahealth.com',
        'practo.com',
        'healinpharma.com',
        'sunpharma.com',
        'cipla.com',
        'mankind.in',
        'alkem.com',
        'lupin.com',
        'zyduscadila.com',
        'drreddy.com',
        'medinova',
    ];

    /**
     * Get effective SerpAPI key — DB setting overrides .env
     */
    private function apiKey(): string
    {
        return \App\Models\Setting::get('ai.serpapi_key', config('services.serpapi.key', ''));
    }

    /**
     * Search for a pharmaceutical product image and download the best one.
     * Returns local temp file path or null if none found.
     */
    public function searchAndDownloadBest(string $productName, string $slug): ?string
    {
        $key = $this->apiKey();

        if (empty($key)) {
            Log::info("ImageSearch: No SerpAPI key configured, skipping search for [{$productName}]");
            return null;
        }

        $tempDir = str_replace('\\', '/', storage_path('app/temp'));
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $candidates = $this->searchCandidates($productName, $key);

        if (empty($candidates)) {
            Log::warning("ImageSearch: No image candidates found for [{$productName}]");
            return null;
        }

        // Try downloading top 3 candidates, return first valid one
        $downloaded = [];
        foreach (array_slice($candidates, 0, 3) as $index => $url) {
            $path = $this->downloadImage($url, $tempDir, $slug, $index);
            if ($path) {
                $downloaded[] = $path;
            }
        }

        if (empty($downloaded)) {
            Log::warning("ImageSearch: All candidates failed to download for [{$productName}]");
            return null;
        }

        // Pick largest valid image (most likely to be highest quality)
        $best = collect($downloaded)
            ->filter(fn($p) => $this->isValidImage($p))
            ->sortByDesc(fn($p) => filesize($p))
            ->first();

        // Clean up unused downloaded files
        foreach ($downloaded as $path) {
            if ($path !== $best && file_exists($path)) {
                @unlink($path);
            }
        }

        if (!$best) {
            Log::warning("ImageSearch: No valid image passed local validation for [{$productName}]");
            return null;
        }

        Log::info("ImageSearch: Best image selected for [{$productName}]", [
            'path' => $best,
            'size' => filesize($best),
        ]);

        return $best;
    }

    /**
     * Call SerpAPI to get image URLs.
     */
    private function searchCandidates(string $productName, string $key): array
    {
        try {
            $response = Http::timeout(20)->get('https://serpapi.com/search', [
                'engine'  => 'google_images',
                'q'       => '"' . $productName . '" pharmaceutical packaging India medicine',
                'api_key' => $key,
                'num'     => 10,
                'safe'    => 'active',
                'ijn'     => '0',
            ]);

            if (!$response->successful()) {
                Log::error("SerpAPI error: " . $response->status() . " " . $response->body());
                return [];
            }

            $results = $response->json('images_results', []);

            // Filter by trusted domains first, then allow others as fallback
            $trusted  = [];
            $fallback = [];

            foreach ($results as $result) {
                $url = $result['original'] ?? $result['thumbnail'] ?? null;
                if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
                    continue;
                }

                $domain = parse_url($url, PHP_URL_HOST) ?? '';
                $isTrusted = collect(self::TRUSTED_DOMAINS)
                    ->contains(fn($d) => str_contains($domain, $d));

                if ($isTrusted) {
                    $trusted[] = $url;
                } else {
                    $fallback[] = $url;
                }
            }

            Log::info("ImageSearch: Found candidates for [{$productName}]", [
                'trusted'  => count($trusted),
                'fallback' => count($fallback),
            ]);

            // Prefer trusted domains, fallback if not enough
            $all = array_merge($trusted, $fallback);
            return array_slice($all, 0, 5);

        } catch (\Exception $e) {
            Log::error("SerpAPI exception: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Download an image URL to a local temp file.
     */
    private function downloadImage(string $url, string $tempDir, string $slug, int $index): ?string
    {
        try {
            $response = Http::timeout(20)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $body = $response->body();
            if (strlen($body) < 10_000) { // less than 10KB = likely garbage
                return null;
            }

            $path = "{$tempDir}/{$slug}-ref-{$index}.jpg";
            file_put_contents($path, $body);

            return $path;
        } catch (\Exception $e) {
            Log::warning("ImageSearch download failed [{$index}]: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Local (free, no AI) image validation.
     */
    private function isValidImage(string $path): bool
    {
        if (!file_exists($path) || filesize($path) < 10_000) {
            return false;
        }

        try {
            [$width, $height] = getimagesize($path);
            // Must be at least 300x300 (not 600 — some good images are smaller)
            return $width >= 300 && $height >= 300;
        } catch (\Exception $e) {
            return false;
        }
    }
}
