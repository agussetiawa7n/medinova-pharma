<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AIProductService
{
    // OpenRouter unified API — one key, 200+ models
    private const BASE_URL = 'https://openrouter.ai/api/v1';

    private function apiKey(): string
    {
        return \App\Models\Setting::get('ai.openrouter_api_key', config('services.openrouter.api_key', ''));
    }

    // ── TEXT GENERATION ──

    public function generateProductDetails(string $productName): array
    {
        $model     = \App\Models\Setting::get('ai.text_model', 'openai/gpt-5-mini');
        $temp      = (float) (\App\Models\Setting::get('ai.temperature', '0.7'));
        $maxTokens = (int) (\App\Models\Setting::get('ai.max_tokens', '2000'));

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey(),
            'Content-Type'  => 'application/json',
            'HTTP-Referer'  => config('app.url'),
            'X-Title'       => 'MediNova Pharma',
        ])->post(self::BASE_URL . '/chat/completions', [
            'model'       => $model,
            'temperature' => $temp,
            'max_tokens'  => $maxTokens,
            'messages'    => [
                ['role' => 'system', 'content' => 'You are a pharmaceutical product expert. Return ONLY valid JSON, no markdown, no backticks.'],
                ['role' => 'user',   'content' => $this->buildTextPrompt($productName)],
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenRouter API error: ' . $response->body());
        }

        return $this->parseJson($response->json('choices.0.message.content'));
    }

    private function buildTextPrompt(string $productName): string
    {
        $categories = Category::pluck('name')->implode(', ');
        $brands     = Brand::pluck('name')->implode(', ');

        return <<<PROMPT
You are a pharmaceutical product expert. Generate product details for the medicine below.
Return ONLY a valid JSON object. No explanation, no markdown.

Required JSON fields:
{
  "name": "full product name with strength",
  "short_description": "2-3 sentence summary",
  "description": "detailed HTML with h2, h3, ul tags: uses, benefits, dosage, side effects, warnings",
  "price": float (Indian market price INR),
  "compare_price": float (higher than price for discount),
  "category": "best match from: [{$categories}]",
  "brand": "best match from: [{$brands}]",
  "tags": ["tag1", "tag2", "tag3"],
  "composition": "active ingredient(s) with strength",
  "manufacturer": "company name",
  "storage_conditions": "storage instructions",
  "meta_title": "Buy {name} Online - Best Price | Medinova",
  "meta_description": "SEO description under 160 chars",
  "unit": "strip OR bottle OR tube OR box OR sachet",
  "weight": float (grams),
  "sku": "MED-{SHORT}-{DIGITS}",
  "requires_prescription": boolean,
  "is_featured": false
}

Medicine: {$productName}
PROMPT;
    }

    // ── IMAGE GENERATION via OpenRouter ──

    public function generateImage(string $productName): ?string
    {
        if (!(bool) \App\Models\Setting::get('ai.generate_images', 'true')) {
            return null;
        }

        $model = \App\Models\Setting::get('ai.image_model', 'openai/gpt-5-image-mini');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey(),
                'Content-Type'  => 'application/json',
                'HTTP-Referer'  => config('app.url'),
                'X-Title'       => 'MediNova Pharma',
            ])->post(self::BASE_URL . '/chat/completions', [
                'model'    => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $this->buildImagePrompt($productName)],
                ],
            ]);

            if (!$response->successful()) {
                throw new \Exception('OpenRouter image error: ' . $response->body());
            }

            // OpenRouter returns image URL in the response content or as a separate field
            $data = $response->json();

            // Try to extract image URL from choices
            $content = $data['choices'][0]['message']['content'] ?? '';
            if (filter_var($content, FILTER_VALIDATE_URL)) {
                return $content;
            }

            // Fallback: check for images array in response
            if (!empty($data['choices'][0]['message']['images'])) {
                return $data['choices'][0]['message']['images'][0]['url'] ?? null;
            }

            // If model returns base64 image in content
            if (str_starts_with($content, 'data:image')) {
                $imageData = explode(',', $content)[1] ?? '';
                $tempPath  = storage_path('app/temp/' . Str::slug($productName) . '_raw.png');
                if (!is_dir(dirname($tempPath))) mkdir(dirname($tempPath), 0755, true);
                file_put_contents($tempPath, base64_decode($imageData));
                return $tempPath;
            }

            return $this->placeholderImage($productName);
        } catch (\Exception $e) {
            Log::error("Image generation failed: {$productName}: " . $e->getMessage());
            return $this->placeholderImage($productName);
        }
    }

    private function buildImagePrompt(string $productName): string
    {
        return <<<PROMPT
Ultra-realistic pharmaceutical product photography of "{$productName}"

STRICT composition rules (do not break):
- Pure #FFFFFF white background (studio seamless), no gradient, no texture
- Soft natural shadow directly under product (very subtle, diffused)
- ONE product box centered, occupying ~70% of frame
- Perspective: slight 3D angle (front + right side visible, 10-15 degrees)
- Product name "{$productName}" must be clearly printed on packaging (sharp, readable)
- ONE blister pack (silver foil) placed bottom-right, slightly overlapping box
- Blister pack size ~20-25% width, natural tilt, realistic pill shapes embossed
- No extra objects, no props, no human elements, no reflections clutter

Material & realism:
- Photorealistic cardboard texture (subtle grain, matte or semi-gloss finish)
- Accurate lighting with softbox studio setup (top-left key light, soft shadows)
- High dynamic range, no overexposure, no blown highlights
- Micro details: edges, folds, print clarity, minor imperfections for realism
- True-to-life colors, pharmaceutical design style (clean, minimal, clinical)

Special cases:
- If syrup: replace box with bottle (transparent or amber), add label, remove blister, add small label/tag bottom-right
- If cream/ointment: show tube with cap placed separately at bottom-right

Camera & output:
- Shot on high-end DSLR (85mm lens, f/8, ISO 100)
- Sharp focus, no blur, no noise
- 4K resolution, ultra-detailed
- Square format (1:1), centered composition

Style: Premium e-commerce product image, Amazon/Flipkart quality, hyper-realistic
PROMPT;
    }

    // ── LIST PARSER ──

    public function parseProductList(string $rawText): array
    {
        $lines    = preg_split('/[\r\n,]+/', $rawText);
        $products = [];

        foreach ($lines as $line) {
            $cleaned = preg_replace('/^[\d\.\-\)\s]+/', '', trim($line));
            $cleaned = trim($cleaned, " \t\n\r\0\x0B,;");
            if (strlen($cleaned) >= 3) {
                $products[] = $cleaned;
            }
        }

        return array_values(array_unique($products));
    }

    private function placeholderImage(string $productName): string
    {
        return 'https://placehold.co/1024x1024/ffffff/333333?text=' . urlencode($productName);
    }

    private function parseJson(string $content): array
    {
        $content = preg_replace('/```json\s*/i', '', $content);
        $content = preg_replace('/```\s*/i', '', $content);
        $content = trim($content);

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON from AI: ' . json_last_error_msg());
        }

        return $data;
    }
}
