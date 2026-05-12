<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class ImageEditService
{
    private const OPENROUTER_BASE = 'https://openrouter.ai/api/v1';

    private function apiKey(): string
    {
        return \App\Models\Setting::get('ai.openrouter_api_key', config('services.openrouter.api_key', ''));
    }

    private function imageModel(): string
    {
        return \App\Models\Setting::get('ai.image_model', 'openai/gpt-5-image');
    }

    /**
     * MAIN ENTRY POINT
     * Takes a reference image path, preprocesses it, sends to GPT-5 for editing.
     * Returns path to raw output PNG, or null on failure.
     */
    public function editWithReference(string $refPath, string $productName, string $slug): ?string
    {
        $tempDir = str_replace('\\', '/', storage_path('app/temp'));

        // Step 1: Preprocess the reference image
        $processedPath = $this->preprocess($refPath, $slug, $tempDir);
        if (!$processedPath) {
            Log::warning("ImageEdit: Preprocessing failed for [{$productName}], using original");
            $processedPath = $refPath;
        }

        // Step 2: Try /images/edits endpoint first (best quality)
        $result = $this->tryEditEndpoint($processedPath, $productName, $slug, $tempDir);

        // Step 3: Fallback — /chat/completions with base64 reference image
        if (!$result) {
            Log::info("ImageEdit: /images/edits failed, falling back to multimodal for [{$productName}]");
            $result = $this->tryMultimodalEndpoint($processedPath, $productName, $slug, $tempDir);
        }

        // Cleanup preprocessed temp
        if ($processedPath !== $refPath && file_exists($processedPath)) {
            @unlink($processedPath);
        }

        return $result;
    }

    /**
     * Pure generation (no reference) with locked style prompt.
     * Used when image search fails completely.
     */
    public function generateWithLockedPrompt(string $productName, string $slug): ?string
    {
        $tempDir = str_replace('\\', '/', storage_path('app/temp'));
        $model   = $this->imageModel();
        $prompt  = $this->buildLockedGenerationPrompt($productName);

        Log::info("ImageEdit: Pure locked-prompt generation for [{$productName}]");

        try {
            $response = Http::connectTimeout(15)
                ->timeout(180)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey(),
                    'Content-Type'  => 'application/json',
                    'HTTP-Referer'  => config('app.url'),
                    'X-Title'       => 'MediNova Pharma',
                ])
                ->post(self::OPENROUTER_BASE . '/chat/completions', [
                    'model'      => $model,
                    'modalities' => ['image', 'text'],
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            return $this->extractImageFromResponse($response, $slug, $tempDir);

        } catch (\Exception $e) {
            Log::error("ImageEdit: Pure generation failed for [{$productName}]: " . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────
    // PRIVATE METHODS
    // ─────────────────────────────────────────────────────────

    /**
     * Resize + normalize reference image before sending to AI.
     */
    private function preprocess(string $inputPath, string $slug, string $tempDir): ?string
    {
        try {
            $outputPath = "{$tempDir}/{$slug}-preprocessed.jpg";
            $manager    = ImageManager::usingDriver(GdDriver::class);

            // Intervention Image v4: scaleDown then save (format auto-detected from extension)
            $manager->decode($inputPath)
                ->scaleDown(1024, 1024)
                ->save($outputPath, quality: 92);

            Log::info("ImageEdit: Preprocessed [{$slug}]", [
                'input_size'  => filesize($inputPath),
                'output_size' => filesize($outputPath),
            ]);

            return $outputPath;
        } catch (\Exception $e) {
            Log::warning("ImageEdit: Preprocess failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Try OpenAI /images/edits multipart endpoint.
     * This is the BEST approach — preserves original packaging.
     */
    private function tryEditEndpoint(string $processedPath, string $productName, string $slug, string $tempDir): ?string
    {
        try {
            $model  = $this->imageModel();
            $prompt = $this->buildEditPrompt($productName);

            Log::info("ImageEdit: Trying /images/edits for [{$productName}]", ['model' => $model]);

            $response = Http::connectTimeout(15)
                ->timeout(200)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey(),
                    'HTTP-Referer'  => config('app.url'),
                    'X-Title'       => 'MediNova Pharma',
                ])
                ->asMultipart()
                ->attach('image[]', file_get_contents($processedPath), basename($processedPath), ['Content-Type' => 'image/jpeg'])
                ->attach('prompt', $prompt)
                ->attach('model', $model)
                ->attach('n', '1')
                ->attach('size', '1024x1024')
                ->post(self::OPENROUTER_BASE . '/images/edits');

            if (!$response->successful()) {
                Log::warning("ImageEdit: /images/edits HTTP {$response->status()}: " . substr($response->body(), 0, 300));
                return null;
            }

            // OpenAI /images/edits returns: data[0].b64_json or data[0].url
            $data = $response->json('data.0');
            if (!$data) {
                Log::warning("ImageEdit: /images/edits empty data response");
                return null;
            }

            $rawPath = "{$tempDir}/{$slug}_raw.png";

            if (!empty($data['b64_json'])) {
                $decoded = base64_decode($data['b64_json']);
                file_put_contents($rawPath, $decoded);
                Log::info("ImageEdit: /images/edits SUCCESS (b64) for [{$productName}]", ['size' => strlen($decoded)]);
                return $rawPath;
            }

            if (!empty($data['url'])) {
                $imgContent = Http::timeout(30)->get($data['url'])->body();
                file_put_contents($rawPath, $imgContent);
                Log::info("ImageEdit: /images/edits SUCCESS (url) for [{$productName}]");
                return $rawPath;
            }

            Log::warning("ImageEdit: /images/edits returned no image data");
            return null;

        } catch (\Exception $e) {
            Log::warning("ImageEdit: /images/edits exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fallback: /chat/completions with reference image embedded as base64.
     * Less precise than edit endpoint but widely supported.
     */
    private function tryMultimodalEndpoint(string $processedPath, string $productName, string $slug, string $tempDir): ?string
    {
        try {
            $model    = $this->imageModel();
            $prompt   = $this->buildEditPrompt($productName);
            $base64   = base64_encode(file_get_contents($processedPath));
            $dataUri  = 'data:image/jpeg;base64,' . $base64;

            Log::info("ImageEdit: Trying multimodal /chat/completions for [{$productName}]");

            $response = Http::connectTimeout(15)
                ->timeout(200)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey(),
                    'Content-Type'  => 'application/json',
                    'HTTP-Referer'  => config('app.url'),
                    'X-Title'       => 'MediNova Pharma',
                ])
                ->post(self::OPENROUTER_BASE . '/chat/completions', [
                    'model'      => $model,
                    'modalities' => ['image', 'text'],
                    'messages'   => [
                        [
                            'role'    => 'user',
                            'content' => [
                                [
                                    'type'      => 'image_url',
                                    'image_url' => ['url' => $dataUri],
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                ]);

            return $this->extractImageFromResponse($response, $slug, $tempDir);

        } catch (\Exception $e) {
            Log::error("ImageEdit: Multimodal exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse image out of /chat/completions response (handles multiple formats).
     */
    private function extractImageFromResponse($response, string $slug, string $tempDir): ?string
    {
        if (!$response->successful()) {
            Log::warning("ImageEdit: Response HTTP {$response->status()}: " . substr($response->body(), 0, 300));
            return null;
        }

        $rawPath = "{$tempDir}/{$slug}_raw.png";
        $message = $response->json('choices.0.message', []);

        // Format 1: message.images[] array
        if (!empty($message['images'])) {
            foreach ($message['images'] as $img) {
                $url = $img['image_url']['url'] ?? ($img['url'] ?? null);
                if ($url && str_starts_with($url, 'data:image')) {
                    $decoded = base64_decode(explode(',', $url, 2)[1] ?? '');
                    if ($decoded && strlen($decoded) > 1000) {
                        file_put_contents($rawPath, $decoded);
                        return $rawPath;
                    }
                }
                if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                    file_put_contents($rawPath, Http::timeout(30)->get($url)->body());
                    return $rawPath;
                }
            }
        }

        // Format 2: base64 in content
        $content = $message['content'] ?? '';
        if (str_starts_with(trim($content), 'data:image')) {
            $decoded = base64_decode(explode(',', $content, 2)[1] ?? '');
            if ($decoded && strlen($decoded) > 1000) {
                file_put_contents($rawPath, $decoded);
                return $rawPath;
            }
        }

        // Format 3: URL in content
        if (filter_var(trim($content), FILTER_VALIDATE_URL)) {
            file_put_contents($rawPath, Http::timeout(30)->get(trim($content))->body());
            return $rawPath;
        }

        Log::warning("ImageEdit: Could not extract image from response", [
            'content_preview' => substr($content, 0, 200),
        ]);
        return null;
    }

    /**
     * EDIT PROMPT — "Preserve everything, only enhance presentation"
     * This is fundamentally different from generation prompts.
     */
    private function buildEditPrompt(string $productName): string
    {
        return <<<PROMPT
You are enhancing a pharmaceutical product image. The product is: "{$productName}"

CRITICAL: Use the uploaded reference image as the PRIMARY source of truth.

PRODUCT IDENTITY (MUST MATCH EXACTLY — DO NOT CHANGE):
- Product name on packaging MUST show: {$productName}
- Dosage/strength shown on packaging MUST match exactly as in the name "{$productName}"
- Do NOT change the mg, mcg, ml, IU or any strength/dose value
- Do NOT substitute a different strength or variant

Preserve EXACTLY from the reference image:
- original box colors and full color palette
- original logo design, position, and size
- original typography, font style, and text layout
- original packaging graphics and visual identity
- original branding structure and composition
- all text printed on the packaging

Only transform the presentation into:
- clean ecommerce studio photography quality
- pure white #FFFFFF seamless background (remove any dirty/colored background)
- soft professional shadow directly beneath the product
- improved sharpness and print clarity
- corrected perspective if tilted or distorted
- premium pharmaceutical softbox studio lighting (top-left main, soft front fill)
- square 1:1 composition with product centered at 70% of frame

STRICTLY FORBIDDEN:
- Do NOT change the dosage or strength (mg/ml/mcg/IU)
- Do NOT invent new branding or colors
- Do NOT replace packaging graphics
- Do NOT simplify or redesign the layout
- Do NOT generate placeholder packaging text
- Do NOT add fantasy elements or decorations

This is an image ENHANCEMENT task, NOT a generation task.
PROMPT;
    }

    /**
     * FALLBACK GENERATION PROMPT — when no reference image available.
     * Locked style to prevent AI from going "creative".
     */
    private function buildLockedGenerationPrompt(string $productName): string
    {
        return <<<PROMPT
Ultra-realistic premium pharmaceutical product photography of "{$productName}"

CRITICAL COMPOSITION RULES (STRICTLY FOLLOW):
- Pure solid #FFFFFF seamless studio background
- Background must be completely clean, smooth, textureless, and evenly lit
- NO gradients, NO colored tint, NO environmental background
- ONE main pharmaceutical product only
- Product centered perfectly in frame
- Product occupies approximately 68–72% of total canvas
- Camera angle: professional commercial 3D product perspective
- Front face fully visible + slight right-side visibility (10–15° angle)
- Product name "{$productName}" must appear clearly printed on packaging
- Text must be sharp, readable, realistic pharmaceutical typography
- ONE realistic silver blister foil strip positioned bottom-right
- Blister strip slightly overlaps product naturally
- Blister occupies approximately 20–25% width of image
- Realistic embossed pill cavities visible on foil
- Soft subtle natural shadow directly underneath product only

PACKAGING DESIGN RULES:
- Premium modern pharmaceutical packaging design
- Minimal clean clinical aesthetic
- High-end Indian pharmacy/e-commerce style
- Accurate medicine box proportions, sharp edge folds

LIGHTING SETUP:
- Professional softbox studio lighting
- Main soft light from top-left, very soft fill from front
- Clean commercial catalog lighting — Amazon/Flipkart/e-commerce style

SPECIAL PRODUCT LOGIC:
- If "{$productName}" is syrup/liquid: replace box with pharmaceutical bottle (amber/transparent), add label, remove blister
- If "{$productName}" is cream/gel/ointment: use pharmaceutical tube, cap bottom-right, remove blister
- If "{$productName}" is injection/vial: show vial + box, sterile clinical appearance

SYSTEM STYLE LOCKS:
- Maintain consistent pharmaceutical branding layout
- Maintain consistent camera angle and lighting setup
- Do NOT redesign layout or generate artistic packaging
- Do NOT add fantasy pharmaceutical design
- Do NOT invent random branding

CAMERA: 85mm macro lens, f/8, ISO 100, ultra-sharp focus, 4K quality, 1:1 square format
PROMPT;
    }
}
