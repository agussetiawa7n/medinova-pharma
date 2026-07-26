<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    /** How many candidates to try downloading before giving up. */
    private const MAX_DOWNLOAD_ATTEMPTS = 5;

    /**
     * Trusted pharmaceutical image sources, MOST PREFERRED FIRST.
     * This order is now enforced when ranking candidates.
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
     * Fetch one specific image the admin supplied, bypassing search entirely.
     *
     * Search is a best effort against a third party; for the cases where it
     * cannot find the box, this is the deterministic way to still get the real
     * photo in front of the model.
     */
    public function downloadFrom(string $url, string $slug): ?string
    {
        $this->lastError  = null;
        $this->lastSource = null;

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->lastError = 'That does not look like a valid image URL.';
            return null;
        }

        $tempDir = str_replace('\\', '/', storage_path('app/temp'));
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $path = $this->downloadImage($url, $tempDir, $slug, 0);

        if (!$path) {
            $this->lastError ??= 'Could not download that image URL.';
            return null;
        }

        if (!$this->isValidImage($path)) {
            @unlink($path);
            $this->lastError = 'That image is too small to use as a reference.';
            return null;
        }

        $this->lastSource = parse_url($url, PHP_URL_HOST) ?: $url;

        return $path;
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

        // Walk the candidates in priority order and stop at the first one that
        // downloads AND validates. The old version downloaded a fixed two and
        // then filtered, so if both happened to be blocked it gave up even
        // though good candidates were sitting further down the list.
        foreach (array_slice($candidates, 0, self::MAX_DOWNLOAD_ATTEMPTS) as $index => $url) {
            $path = $this->downloadImage($url, $tempDir, $slug, $index);

            if (!$path) {
                continue;
            }

            if (!$this->isValidImage($path)) {
                @unlink($path);
                $this->lastError = 'Reference image was too small to use.';
                continue;
            }

            $this->lastSource = parse_url($url, PHP_URL_HOST) ?: $url;
            $this->lastError  = null;

            Log::info("ImageSearch: Reference selected for [{$productName}]", [
                'path'   => $path,
                'size'   => filesize($path),
                'source' => $this->lastSource,
            ]);

            return $path;
        }

        $this->lastError ??= 'No candidate image could be downloaded.';
        Log::warning("ImageSearch: No usable reference for [{$productName}]: {$this->lastError}");

        return null;
    }

    /**
     * Collect image URLs, best source first.
     *
     * Runs progressively broader queries and stops as soon as a pass yields
     * results from a trusted pharmacy site. A single query of
     * '"name" pharmaceutical packaging India medicine' returned almost nothing
     * for niche brands, and nothing ever aimed at IndiaMart specifically —
     * which is the one source that reliably has the actual box.
     */
    private function searchCandidates(string $productName, string $key): array
    {
        $passes = [
            '"' . $productName . '" site:indiamart.com',
            '"' . $productName . '" medicine tablet packaging',
            $productName . ' medicine',
        ];

        $best = [];

        foreach ($passes as $query) {
            [$trusted, $fallback] = $this->runSearchPass($query, $key, $productName);

            if ($trusted) {
                // A trusted hit is what we want; take it and stop paying for
                // further searches.
                return array_slice(array_merge($trusted, $fallback), 0, 8);
            }

            $best = $best ?: $fallback;
        }

        return array_slice($best, 0, 8);
    }

    /** One SerpAPI call. Returns [trustedUrls, untrustedUrls], best first. */
    private function runSearchPass(string $query, string $key, string $productName): array
    {
        try {
            $response = Http::timeout(20)->get('https://serpapi.com/search', [
                'engine'  => 'google_images',
                'q'       => $query,
                'api_key' => $key,
                'num'     => 20,
                'safe'    => 'active',
                'ijn'     => '0',
            ]);

            if (!$response->successful()) {
                $this->lastError = "Image search API returned HTTP {$response->status()}.";
                Log::error("SerpAPI error: " . $response->status() . " " . $response->body());
                return [[], []];
            }

            $scored   = [];
            $fallback = [];

            foreach ($response->json('images_results', []) as $result) {
                // Keep BOTH URLs per result. 'original' points at the source
                // site and is the good one, but it is exactly what gets
                // hotlink-blocked; 'thumbnail' is SerpAPI's own copy and always
                // serves. Taking only one meant a blocked site dropped out.
                $urls = array_values(array_filter(
                    [$result['original'] ?? null, $result['thumbnail'] ?? null],
                    fn ($u) => $u && filter_var($u, FILTER_VALIDATE_URL)
                ));

                if (!$urls) {
                    continue;
                }

                // Rank by position in TRUSTED_DOMAINS, so IndiaMart (index 0)
                // always outranks the rest. Previously the trusted bucket kept
                // Google's own ordering, which made the constant's order — and
                // IndiaMart's place at the top of it — purely decorative.
                $rank = $this->trustRank((string) (parse_url($urls[0], PHP_URL_HOST) ?? ''));

                foreach ($urls as $url) {
                    if ($rank !== null) {
                        $scored[] = ['rank' => $rank, 'url' => $url];
                    } else {
                        $fallback[] = $url;
                    }
                }
            }

            usort($scored, fn ($a, $b) => $a['rank'] <=> $b['rank']);
            $trusted = array_column($scored, 'url');

            Log::info("ImageSearch: pass for [{$productName}]", [
                'query'    => $query,
                'trusted'  => count($trusted),
                'fallback' => count($fallback),
            ]);

            return [$trusted, $fallback];

        } catch (\Exception $e) {
            $this->lastError = 'Image search failed: ' . $e->getMessage();
            Log::error("SerpAPI exception: " . $e->getMessage());
            return [[], []];
        }
    }

    /** Position in TRUSTED_DOMAINS (0 = most preferred), or null if untrusted. */
    private function trustRank(string $host): ?int
    {
        foreach (array_values(self::TRUSTED_DOMAINS) as $index => $domain) {
            if ($domain !== '' && str_contains($host, $domain)) {
                return $index;
            }
        }

        return null;
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
            $response = Http::timeout(8)
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
