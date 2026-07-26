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

    /** HTTP status of the most recent download attempt, for dead-host tracking. */
    private ?int $lastStatus = null;

    /**
     * The best real-product-photo URL that this SERVER cannot fetch but a
     * browser can — e.g. an IndiaMart original behind the datacenter 444 block.
     * Handed to the admin's browser so it downloads the photo from their own IP.
     */
    private ?string $lastCandidateUrl  = null;
    private ?int    $lastCandidateRank = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function lastSource(): ?string
    {
        return $this->lastSource;
    }

    public function lastCandidateUrl(): ?string
    {
        return $this->lastCandidateUrl;
    }

    /** How many candidates to try downloading before giving up. */
    private const MAX_DOWNLOAD_ATTEMPTS = 5;

    /**
     * Hosts that refuse to serve a web server, whatever headers it sends.
     *
     * Measured, not assumed. The same IndiaMart CDN URL, same request:
     *   from a home/office IP  → HTTP 200, 265 KB PNG
     *   from a datacenter IP   → HTTP 444 (nginx closes without responding)
     *
     * 444 is an explicit nginx deny, and it fires on IP reputation rather than
     * on User-Agent or Referer — a public image proxy (images.weserv.nl, also a
     * datacenter) gets the same 444. Hostinger is a datacenter, so the origin
     * URL can never be fetched from there and retrying it only burns attempts.
     * The search engine's own copy of the photo is used instead; Google's CDN
     * was verified to serve datacenter IPs normally.
     */
    private const SERVER_BLOCKED_HOSTS = [
        'imimg.com',
    ];

    /** True when this host is known to reject fetches from a server. */
    private function isServerBlocked(string $host): bool
    {
        foreach (self::SERVER_BLOCKED_HOSTS as $blocked) {
            if (str_contains($host, $blocked)) {
                return true;
            }
        }

        return false;
    }

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

        // An admin upload is already on disk. This is the one path that always
        // works: their browser can fetch IndiaMart even though this server
        // cannot, so the file arrives here instead of being downloaded here.
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return $this->copyLocalReference($url, $slug);
        }

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
     * Take an already-stored file (an admin upload) as the reference.
     *
     * Copied rather than used in place, because generateImage() deletes the
     * reference when it is done and the stored upload must survive a retry.
     */
    private function copyLocalReference(string $path, string $slug): ?string
    {
        if (!is_file($path) || !is_readable($path)) {
            $this->lastError = 'The uploaded reference photo is no longer on the server. Upload it again.';
            return null;
        }

        $tempDir = str_replace('\\', '/', storage_path('app/temp'));
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $copy = "{$tempDir}/{$slug}-ref-upload.jpg";

        if (!@copy($path, $copy)) {
            $this->lastError = 'Could not read the uploaded reference photo.';
            return null;
        }

        if (!$this->isValidImage($copy)) {
            @unlink($copy);
            $this->lastError = 'That upload is too small to use as a reference (needs to be at least 150x150).';
            return null;
        }

        $this->lastSource = 'your upload';

        return $copy;
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

        $this->lastCandidateUrl  = null;
        $this->lastCandidateRank = null;

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
        // A host that has already slammed the door stays shut for the rest of
        // this run. Without this, five IndiaMart URLs used up all five attempts
        // on five identical 444s and the engine's own copy was never reached.
        $deadHosts = [];

        foreach (array_slice($candidates, 0, self::MAX_DOWNLOAD_ATTEMPTS) as $index => $url) {
            $host = (string) (parse_url($url, PHP_URL_HOST) ?? '');

            if ($host !== '' && isset($deadHosts[$host])) {
                continue;
            }

            $path = $this->downloadImage($url, $tempDir, $slug, $index);

            if (!$path) {
                if ($host !== '' && $this->lastStatus !== null && in_array($this->lastStatus, [403, 429, 444], true)) {
                    $deadHosts[$host] = true;
                }

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
                $original  = $result['original'] ?? null;
                $thumbnail = $result['thumbnail'] ?? null;

                // IndiaMart's CDN answers a server with 444 no matter what, so
                // its original URL is dead weight — putting it first meant every
                // IndiaMart hit wasted a download attempt before the engine's
                // own copy was even tried. Demote it behind the thumbnail.
                $originalHost = (string) (parse_url((string) $original, PHP_URL_HOST) ?? '');

                $originalBlocked = $original && $this->isServerBlocked($originalHost);

                $ordered = $originalBlocked
                    ? [$thumbnail, $original]
                    : [$original, $thumbnail];

                $urls = array_values(array_filter(
                    $ordered,
                    fn ($u) => $u && filter_var($u, FILTER_VALIDATE_URL)
                ));

                if (!$urls) {
                    continue;
                }

                // Rank by position in TRUSTED_DOMAINS, so IndiaMart (index 0)
                // always outranks the rest. Previously the trusted bucket kept
                // Google's own ordering, which made the constant's order — and
                // IndiaMart's place at the top of it — purely decorative.
                //
                // Ranked on the ORIGINAL's host, never on $urls[0]: the source
                // site is what makes a photo trustworthy, and for a blocked host
                // $urls[0] is now the engine's thumbnail on a Google domain,
                // which would otherwise score as untrusted and lose the ranking.
                $rank = $this->trustRank($originalHost ?: (string) (parse_url($urls[0], PHP_URL_HOST) ?? ''));

                // Remember the best real photo the server itself can never get,
                // so the browser can fetch it from the admin's IP. Only blocked
                // hosts qualify — anything the server can download it already
                // does, and does not need a client round-trip.
                if ($originalBlocked && $rank !== null
                    && ($this->lastCandidateRank === null || $rank < $this->lastCandidateRank)) {
                    $this->lastCandidateUrl  = $original;
                    $this->lastCandidateRank = $rank;
                }

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
        $this->lastStatus = null;

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

            $this->lastStatus = $response->status();

            if (!$response->successful()) {
                $host = (string) (parse_url($url, PHP_URL_HOST) ?: $url);

                // 444 is not "not found" — it is the site refusing to talk to a
                // server at all. Saying so tells the admin the one thing that
                // actually helps: no retry from here will ever succeed.
                $this->lastError = $response->status() === 444 || $this->isServerBlocked($host)
                    ? "{$host} blocks downloads from web servers (HTTP {$response->status()}). "
                        . 'Save the photo on your own computer and upload it on this card instead.'
                    : "Reference download returned HTTP {$response->status()} from {$host}";

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

        // 150px, not 200. For sites that block server-side fetches the search
        // engine's thumbnail is the ONLY copy we can get, and Google sizes those
        // by aspect ratio — a wide box photo comes back around 260x195, which the
        // old floor rejected. Losing the real box to a 5px shortfall is far worse
        // than a slightly soft reference: without one the model invents packaging.
        return $width >= 150 && $height >= 150;
    }
}
