<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AIProductService
{
    // ── TEXT GENERATION: Multi-provider router ──

    public function generateProductDetails(string $productName): array
    {
        $provider = \App\Models\Setting::get('ai.text_model', 'openai/gpt-4o-mini');

        return match (true) {
            str_starts_with($provider, 'openai/')    => $this->callOpenAI($productName, $provider),
            str_starts_with($provider, 'anthropic/') => $this->callAnthropic($productName),
            str_starts_with($provider, 'google/')    => $this->callGemini($productName),
            default                                  => $this->callOpenAI($productName, 'gpt-4o-mini'),
        };
    }

    private function callOpenAI(string $productName, string $provider): array
    {
        $model    = explode('/', $provider)[1] ?? 'gpt-4o-mini';
        $apiKey   = config('services.openai.api_key');
        $temp     = (float) (\App\Models\Setting::get('ai.temperature', '0.7'));
        $maxTokens = (int) (\App\Models\Setting::get('ai.max_tokens', '2000'));

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model'       => $model,
            'temperature' => $temp,
            'max_tokens'  => $maxTokens,
            'messages'    => [
                ['role' => 'system', 'content' => 'You are a pharmaceutical product expert. Return ONLY valid JSON, no explanation, no markdown backticks.'],
                ['role' => 'user',   'content' => $this->buildTextPrompt($productName)],
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API error: ' . $response->body());
        }

        return $this->parseJsonResponse($response->json('choices.0.message.content'));
    }

    private function callAnthropic(string $productName): array
    {
        $maxTokens = (int) (\App\Models\Setting::get('ai.max_tokens', '2000'));

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
            'Content-Type'      => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-3-5-sonnet-20241022',
            'max_tokens' => $maxTokens,
            'system'     => 'You are a pharmaceutical product expert. Return ONLY valid JSON, no explanation, no markdown.',
            'messages'   => [['role' => 'user', 'content' => $this->buildTextPrompt($productName)]],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Anthropic API error: ' . $response->body());
        }

        return $this->parseJsonResponse($response->json('content.0.text'));
    }

    private function callGemini(string $productName): array
    {
        $apiKey = config('services.google_ai.api_key');
        $temp   = (float) (\App\Models\Setting::get('ai.temperature', '0.7'));

        $response = Http::post(
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key={$apiKey}",
            [
                'contents' => [['parts' => [['text' => $this->buildTextPrompt($productName)]]]],
                'generationConfig' => ['temperature' => $temp, 'maxOutputTokens' => (int) (\App\Models\Setting::get('ai.max_tokens', '2000'))],
            ]
        );

        if (!$response->successful()) {
            throw new \Exception('Gemini API error: ' . $response->body());
        }

        return $this->parseJsonResponse($response->json('candidates.0.content.parts.0.text'));
    }

    private function buildTextPrompt(string $productName): string
    {
        $categories = Category::pluck('name')->implode(', ');
        $brands     = Brand::pluck('name')->implode(', ');

        return <<<PROMPT
You are a pharmaceutical product expert. Given the medicine name below, generate accurate product details.
Return ONLY a valid JSON object. No explanation, no markdown backticks.

JSON fields required:
{
  "name": "full product name with strength",
  "short_description": "2-3 sentence summary of what this medicine does",
  "description": "detailed HTML with h2, h3, ul tags covering: uses, benefits, dosage, side effects, warnings",
  "price": float (realistic Indian market price in INR),
  "compare_price": float (higher than price for discount display),
  "category": "best matching category from: [{$categories}]",
  "brand": "best matching brand from: [{$brands}]",
  "tags": ["relevant", "tag", "array"],
  "composition": "active ingredient(s) with strength",
  "manufacturer": "manufacturing company name",
  "storage_conditions": "storage instructions",
  "meta_title": "Buy {product name} Online - Best Price | Medinova",
  "meta_description": "SEO description under 160 characters",
  "unit": "strip OR bottle OR tube OR box OR sachet",
  "weight": float (approximate weight in grams),
  "sku": "MED-{UPPERCASE_SHORT}-{3_DIGITS}",
  "requires_prescription": boolean,
  "is_featured": false
}

Medicine name: {$productName}
PROMPT;
    }

    // ── IMAGE GENERATION: Multi-provider router ──

    public function generateImage(string $productName): ?string
    {
        if (!(bool) \App\Models\Setting::get('ai.generate_images', 'true')) {
            return null;
        }

        $provider = \App\Models\Setting::get('ai.image_model', 'openai/dall-e-3');

        try {
            return match (true) {
                str_starts_with($provider, 'openai/')      => $this->generateDallE($productName, $provider),
                str_starts_with($provider, 'stability/')   => $this->generateStability($productName),
                str_starts_with($provider, 'blackforest/') => $this->generateFlux($productName),
                $provider === 'placeholder'                 => $this->placeholderImage($productName),
                default                                     => $this->generateDallE($productName, 'dall-e-3'),
            };
        } catch (\Exception $e) {
            Log::error("Image generation failed for {$productName}: " . $e->getMessage());
            return $this->placeholderImage($productName);
        }
    }

    private function generateDallE(string $productName, string $provider): string
    {
        $model  = explode('/', $provider)[1] ?? 'dall-e-3';
        $apiKey = config('services.openai.api_key');
        $style  = \App\Models\Setting::get('ai.image_style', 'natural');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.openai.com/v1/images/generations', [
            'model'           => $model,
            'prompt'          => $this->buildImagePrompt($productName),
            'n'               => 1,
            'size'            => '1024x1024',
            'quality'         => $model === 'dall-e-3' ? 'hd' : 'standard',
            'style'           => $style,
            'response_format' => 'url',
        ]);

        if (!$response->successful()) {
            throw new \Exception('DALL-E API error: ' . $response->body());
        }

        return $response->json('data.0.url');
    }

    private function generateStability(string $productName): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.stability.api_key'),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->post('https://api.stability.ai/v2beta/stable-image/generate/sd3', [
            'prompt'        => $this->buildImagePrompt($productName),
            'output_format' => 'png',
            'width'         => 1024,
            'height'        => 1024,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Stability API error: ' . $response->body());
        }

        $imageData = base64_decode($response->json('artifacts.0.base64'));
        $tempPath  = storage_path('app/temp/' . Str::slug($productName) . '_raw.png');
        if (!is_dir(dirname($tempPath))) mkdir(dirname($tempPath), 0755, true);
        file_put_contents($tempPath, $imageData);
        return $tempPath;
    }

    private function generateFlux(string $productName): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.flux.api_key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.bfl.ml/v1/flux-pro-1.1', [
            'prompt' => $this->buildImagePrompt($productName),
            'width'  => 1024,
            'height' => 1024,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Flux API error: ' . $response->body());
        }

        return $this->pollFluxResult($response->json('id'));
    }

    private function pollFluxResult(string $requestId): string
    {
        for ($i = 0; $i < 30; $i++) {
            sleep(2);
            $result = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.flux.api_key'),
            ])->get("https://api.bfl.ml/v1/get_result?id={$requestId}");

            if ($result->json('status') === 'Ready') {
                return $result->json('result.sample');
            }
        }
        throw new \Exception('Flux image generation timed out');
    }

    private function placeholderImage(string $productName): string
    {
        return 'https://placehold.co/1024x1024/ffffff/333333?text=' . urlencode($productName);
    }

    private function buildImagePrompt(string $productName): string
    {
        $style = \App\Models\Setting::get('ai.image_style', 'natural');

        return <<<PROMPT
Professional pharmaceutical product photography for e-commerce.
Product: {$productName}

COMPOSITION RULES (follow exactly):
- Pure white background, no shadows, no gradients, no surfaces
- ONE product box placed at CENTER, occupying 65-70% of frame height
- Product box must show the product name clearly on front face
- ONE blister strip OR foil strip at BOTTOM-RIGHT corner, partially overlapping box
- Strip occupies 20-25% of frame width, slightly angled
- NO other objects, people, hands, surfaces, or scenery

QUALITY RULES:
- Sharp focus, high contrast, well-lit, no overexposure
- Text on packaging legible, crisp edges
- Square 1:1 aspect ratio

EXCEPTIONS:
- Syrup/liquid: BOTTLE at center, small label at bottom-right (not strip)
- Cream/ointment/gel: TUBE at center, cap at bottom-right
- Injection/vial: VIAL at center, small ampoule at bottom-right
- Powder/sachet: SACHET PACK at center, single sachet at bottom-right

Style: Clean, clinical, e-commerce product shot. {$style}.
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

    // ── HELPERS ──

    private function parseJsonResponse(string $content): array
    {
        $content = preg_replace('/```json\s*/i', '', $content);
        $content = preg_replace('/```\s*/i', '', $content);
        $content = trim($content);

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('AI returned invalid JSON: ' . json_last_error_msg() . '. Raw: ' . substr($content, 0, 200));
        }

        return $data;
    }
}
