<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImageSearchService
{
    /**
     * Why no reference image came back, and which site the chosen one came
     * from. The pipeline degrades silently from "edit the real box" to "invent
     * a box", and nothing downstream could tell the two apart.
     */
    private ?string $lastError  = null;
    private ?string $lastSource = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function lastSource(): ?string
    {
        return $this->lastSource;
    }

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
            $this->lastError = 'No SerpAPI key configured, so no real product photo could be looked up.';
            Log::info("ImageSearch: No SerpAPI key configured, skipping search for [{$productName}]");
            return null;
        }

        $tempDir = str_replace('\\', '/', storage_path('app/temp'));
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $candidates = $this->searchCandidates($productName, $key);

        if (empty($candidates)) {
            $this->lastError = 'Image search returned no usable results for this product name.';
            Log::warning("ImageSearch: No image candidates found for [{$productName}]");
            return null;
        }

        // Two candidates, not three. The whole image step shares one request against
        // a 300s host cap, and 20s downloads plus a 200s Fal call could exceed it —
        // the request was killed mid-flight and the row left stuck in `generating`.
        $downloaded = [];
        $sourceOf   = [];
        foreach (array_slice($candidates, 0, 2) as $index => $url) {
            $path = $this->downloadImage($url, $tempDir, $slug, $index);
            if ($path) {
                $downloaded[] = $path;
                $sourceOf[$path] = parse_url($url, PHP_URL_HOST) ?: $url;
            }
        }

        if (empty($downloaded)) {
            $this->lastError ??= 'Every candidate image failed to download.';
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
            $this->lastError = 'Downloaded reference images were too small to use.';
            Log::warning("ImageSearch: No valid image passed local validation for [{$productName}]");
            return null;
        }

        $this->lastSource = $sourceOf[$best] ?? null;
        $this->lastError  = null;

        Log::info("ImageSearch: Best image selected for [{$productName}]", [
            'path'   => $best,
            'size'   => filesize($best),
            'source' => $this->lastSource,
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
                $this->lastError = "Image search API returned HTTP {$response->status()}.";
                Log::error("SerpAPI error: " . $response->status() . " " . $response->body());
                return [];
            }

            $results = $response->json('images_results', []);

            // Filter by trusted domains first, then allow others as fallback
            $trusted  = [];
            $fallback = [];

            foreach ($results as $result) {
                // Keep BOTH URLs per result. 'original' points at the source
                // site and is the good one, but it is exactly what gets
                // hotlink-blocked; 'thumbnail' is SerpAPI's own copy and always
                // serves. Taking only one of them meant a blocked site removed
                // that result from consideration entirely.
                $urls = array_values(array_filter(
                    [$result['original'] ?? null, $result['thumbnail'] ?? null],
                    fn ($u) => $u && filter_var($u, FILTER_VALIDATE_URL)
                ));

                if (!$urls) {
                    continue;
                }

                $domain = parse_url($urls[0], PHP_URL_HOST) ?? '';
                $isTrusted = collect(self::TRUSTED_DOMAINS)
                    ->contains(fn($d) => str_contains($domain, $d));

                foreach ($urls as $url) {
                    if ($isTrusted) {
                        $trusted[] = $url;
                    } else {
                        $fallback[] = $url;
                    }
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
     *
     * Sends browser-like headers. A bare Guzzle request carries no User-Agent
     * and no Referer, and the pharmacy CDNs this searches — IndiaMart, 1mg,
     * Amazon — answer those with 403 or a few hundred bytes of HTML. Every
     * reference download was being rejected, which silently pushed the pipeline
     * onto pure AI generation, i.e. invented packaging instead of the real box.
     */
    private function downloadImage(string $url, string $tempDir, string $slug, int $index): ?string
    {
        try {
            $response = Http::timeout(12)
                ->withHeaders([
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
                                       . '(KHTML, like Gecko) Chrome/125.0 Safari/537.36',
                    'Accept'          => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Referer'         => $this->refererFor($url),
                ])
                ->get($url);

            if (!$response->successful()) {
                $this->lastError = "Reference download returned HTTP {$response->status()} from "
                    . (parse_url($url, PHP_URL_HOST) ?: $url);
                Log::warning("ImageSearch download [{$index}] HTTP {$response->status()} for {$url}");
                return null;
            }

            $body = $response->body();

            // 3KB, not 10KB. The old floor discarded perfectly usable search
            // thumbnails, which are the only thing that survives when a site
            // blocks hotlinking of the full-size image.
            if (strlen($body) < 3_000) {
                $this->lastError = 'Reference download was too small to be a usable image ('
                    . strlen($body) . ' bytes).';
                return null;
            }

            $path = "{$tempDir}/{$slug}-ref-{$index}.jpg";
            file_put_contents($path, $body);

            return $path;
        } catch (\Exception $e) {
            $this->lastError = 'Reference download failed: ' . $e->getMessage();
            Log::warning("ImageSearch download failed [{$index}]: " . $e->getMessage());
            return null;
        }
    }

    /** Some CDNs only serve an image when the Referer looks like its own site. */
    private function refererFor(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host ? 'https://' . $host . '/' : 'https://www.google.com/';
    }

    /**
     * Local (free, no AI) image validation.
     */
    private function isValidImage(string $path): bool
    {
        if (!file_exists($path) || filesize($path) < 3_000) {
            return false;
        }

        // getimagesize() returns false — it does not throw — for anything that
        // is not a readable image, so the old try/catch never fired and the
        // destructure quietly produced nulls.
        $size = @getimagesize($path);

        if ($size === false) {
            return false;
        }

        [$width, $height] = $size;

        // 200px, not 300. A search thumbnail of a real medicine box is a far
        // better reference for the AI than no reference at all — without one it
        // invents packaging from scratch, which is what produced the generic
        // "dummy" boxes.
        return $width >= 200 && $height >= 200;
    }
}
