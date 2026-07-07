<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class ImageEditService
{
    private const FAL_BASE = 'https://fal.run';

    private function apiKey(): string
    {
        return \App\Models\Setting::get('ai.fal_api_key', config('services.fal.api_key', ''));
    }

    private function imageModel(): string
    {
        return \App\Models\Setting::get('ai.image_model', 'gpt-image-1-mini');
    }

    /**
     * MAIN ENTRY POINT
     * Takes a reference image path, preprocesses it, sends to Fal.ai for editing.
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

        // Step 2: Try Fal.ai image-to-image editing endpoint
        $result = $this->tryEditEndpoint($processedPath, $productName, $slug, $tempDir);

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

        Log::info("ImageEdit: Pure locked-prompt generation for [{$productName}] via Fal.ai using {$model}");

        try {
            $response = Http::connectTimeout(15)
                ->timeout(180)
                ->withHeaders([
                    'Authorization' => 'Key ' . $this->apiKey(),
                    'Content-Type'  => 'application/json',
                ])
                ->post(self::FAL_BASE . '/fal-ai/' . $model, [
                    'prompt'        => $prompt,
                    'image_size'    => '1024x1024',
                    'quality'       => 'high',
                ]);

            if (!$response->successful()) {
                Log::warning("ImageEdit: Pure generation HTTP {$response->status()}: " . substr($response->body(), 0, 300));
                return null;
            }

            $url = $response->json('images.0.url');
            if (!$url) {
                Log::warning("ImageEdit: Pure generation returned no image URL");
                return null;
            }

            $rawPath = "{$tempDir}/{$slug}_raw.png";
            $imgContent = Http::timeout(30)->get($url)->body();
            file_put_contents($rawPath, $imgContent);

            Log::info("ImageEdit: Pure generation SUCCESS for [{$productName}]");
            return $rawPath;

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
     * Try Fal.ai image-to-image editing endpoint.
     */
    private function tryEditEndpoint(string $processedPath, string $productName, string $slug, string $tempDir): ?string
    {
        try {
            $model  = $this->imageModel();
            $prompt = $this->buildEditPrompt($productName);
            $base64 = base64_encode(file_get_contents($processedPath));
            $dataUri = 'data:image/jpeg;base64,' . $base64;

            Log::info("ImageEdit: Trying fal.ai edit endpoint for [{$productName}] using {$model}/edit");

            $response = Http::connectTimeout(15)
                ->timeout(200)
                ->withHeaders([
                    'Authorization' => 'Key ' . $this->apiKey(),
                    'Content-Type'  => 'application/json',
                ])
                ->post(self::FAL_BASE . '/fal-ai/' . $model . '/edit', [
                    'prompt'     => $prompt,
                    'image_urls' => [$dataUri],
                    'image_size' => '1024x1024',
                    'quality'    => 'high',
                ]);

            if (!$response->successful()) {
                Log::warning("ImageEdit: Fal.ai edit HTTP {$response->status()}: " . substr($response->body(), 0, 300));
                return null;
            }

            $url = $response->json('images.0.url');
            if (!$url) {
                Log::warning("ImageEdit: Fal.ai edit returned no image URL");
                return null;
            }

            $rawPath = "{$tempDir}/{$slug}_raw.png";
            $imgContent = Http::timeout(30)->get($url)->body();
            file_put_contents($rawPath, $imgContent);

            Log::info("ImageEdit: Fal.ai edit SUCCESS for [{$productName}]");
            return $rawPath;

        } catch (\Exception $e) {
            Log::warning("ImageEdit: Fal.ai edit exception: " . $e->getMessage());
            return null;
        }
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
