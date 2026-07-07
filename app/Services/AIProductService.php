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

    // ── LIGHTWEIGHT PING ──

    public function ping(): array
    {
        $response = Http::connectTimeout(5)->timeout(8)->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey(),
            'Content-Type'  => 'application/json',
        ])->post(self::BASE_URL . '/chat/completions', [
            'model'       => \App\Models\Setting::get('ai.text_model', 'openai/gpt-4o'),
            'max_tokens'  => 5,
            'messages'    => [
                ['role' => 'user', 'content' => 'Say OK'],
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('API error: ' . $response->body());
        }

        return $response->json();
    }

    // ── TEXT GENERATION ──

    public function generateProductDetails(string $productName): array
    {
        set_time_limit(60);

        $model     = \App\Models\Setting::get('ai.text_model', 'openai/gpt-4o');
        $temp      = (float) (\App\Models\Setting::get('ai.temperature', '0.3'));
        $maxTokens = (int) (\App\Models\Setting::get('ai.max_tokens', '2000'));

        $systemPrompt = <<<'SYS'
You are a pharmaceutical product data specialist with deep knowledge of Indian and international pharma brands, generics, and OTC products.

CRITICAL ACCURACY RULES — FOLLOW STRICTLY:
1. NEVER fabricate or guess manufacturer, brand, or composition. If you are not 100% certain, SEARCH THE WEB or write "Unknown".
2. The product name itself often contains the brand name and strength — extract data FROM the name first.
3. For Indian pharma products: carefully identify the REAL manufacturer (e.g., Healing Pharma, Sunrise Remedies, Cipla, Sun Pharma, Mankind, etc.). Do NOT default to Cipla or any big brand unless you are certain.
4. The brand is usually the FIRST word(s) in the product name before the strength/dosage form (e.g., "Malegra Oral Jelly" → brand is "Malegra", NOT "Cipla").
5. Composition must match the ACTUAL active ingredient for that specific branded product.
6. Prices should be realistic US wholesale/retail pharmacy prices in USD.
7. Return ONLY valid JSON. No markdown, no backticks, no explanation text.
SYS;

        $response = Http::connectTimeout(10)->timeout(40)->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey(),
            'Content-Type'  => 'application/json',
            'HTTP-Referer'  => config('app.url'),
            'X-Title'       => 'MediNova Pharma',
        ])->post(self::BASE_URL . '/chat/completions', [
            'model'       => $model,
            'temperature' => $temp,
            'max_tokens'  => $maxTokens,
            'plugins'     => [
                ['id' => 'web'] // Enable OpenRouter web search for live accuracy
            ],
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $this->buildTextPrompt($productName)],
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenRouter API error: ' . $response->body());
        }

        return $this->parseJson($response->json('choices.0.message.content'));
    }

    public function generateCategoryDetails(string $categoryName): array
    {
        set_time_limit(60);

        $model     = \App\Models\Setting::get('ai.text_model', 'openai/gpt-4o');
        $temp      = (float) (\App\Models\Setting::get('ai.temperature', '0.3'));
        $maxTokens = (int) (\App\Models\Setting::get('ai.max_tokens', '2000'));

        $systemPrompt = <<<'SYS'
You are an expert pharmaceutical medical writer and clinical pharmacist.
You generate highly professional, authoritative, and trustable medical content for pharmacy website categories.

CRITICAL COMPLIANCE RULES — YMYL & EEAT:
1. YOUR MONEY OR YOUR LIFE (YMYL): Pharmaceutical information directly affects patient health. All content must be medically accurate, evidence-based, objective, and unbiased. Avoid sensationalism or marketing fluff.
2. EEAT (Experience, Expertise, Authoritativeness, Trustworthiness):
   - Clear, professional explanation of the category's medical scope (definition, symptoms, or conditions addressed).
   - Use correct clinical terminology alongside plain-language explanations to keep it accessible yet highly authoritative.
   - Do NOT give individual medical advice; write at a general educational level.
   - Include clear guidelines on safety, general precautions, and when to seek immediate medical attention.
3. Every description MUST include a stylized medical disclaimer warning users to consult a healthcare professional.
4. Return ONLY valid JSON. No markdown, no backticks, no explanation text.
SYS;

        $response = Http::connectTimeout(10)->timeout(40)->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey(),
            'Content-Type'  => 'application/json',
            'HTTP-Referer'  => config('app.url'),
            'X-Title'       => 'MediNova Pharma',
        ])->post(self::BASE_URL . '/chat/completions', [
            'model'       => $model,
            'temperature' => $temp,
            'max_tokens'  => $maxTokens,
            'plugins'     => [
                ['id' => 'web'] // Enable OpenRouter web search for live accuracy
            ],
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $this->buildCategoryTextPrompt($categoryName)],
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenRouter API error: ' . $response->body());
        }

        return $this->parseJson($response->json('choices.0.message.content'));
    }

    private function buildCategoryTextPrompt(string $categoryName): string
    {
        return <<<PROMPT
Generate comprehensive, YMYL-compliant and EEAT-rich content for the medical category: "{$categoryName}"

Ensure the response contains a detailed, structured HTML description:
1. Overview: Define the medical category, its scope, and clinical significance.
2. Common Conditions: Outline the main conditions or symptoms managed under this category.
3. General Safety Guidelines: Explain key precautions, potential contraindications, and typical instructions for this class of treatments.
4. A prominent, styled medical disclaimer at the bottom or top.
5. Suggest a modern Heroicon v2 outline icon name (e.g., 'heroicon-o-shield-check', 'heroicon-o-heart', 'heroicon-o-sparkles', 'heroicon-o-beaker', 'heroicon-o-eye', etc.) that visually represents this category.

Return this exact JSON structure:
{
"name":"{$categoryName}",
"description":"<h2>Overview of {$categoryName}</h2><p>...</p><h3>Conditions & Treatment Scope</h3><p>...</p><h3>Important Safety & Precautions</h3><ul><li>...</li></ul><div class=\"medical-disclaimer\" style=\"margin-top:20px; padding:15px; border-left:4px solid #ef4444; background-color:#fef2f2; color:#b91c1c; font-size:13px; border-radius:4px;\"><strong>Medical Disclaimer:</strong> The content provided here is for informational and educational purposes only and does not constitute medical advice. Consult a qualified healthcare professional before starting any treatment.</div>",
"icon":"heroicon-o-icon-name",
"meta_title":"Buy {$categoryName} Online | Medinova Pharma",
"meta_description":"Explore medically reviewed treatments for {$categoryName} at Medinova Pharma. Safe, authentic, and fast shipping."
}
PROMPT;
    }

    private function buildTextPrompt(string $productName): string
    {
        $categories = Category::pluck('name')->implode(', ');
        $brands     = Brand::pluck('name')->implode(', ');

        return <<<PROMPT
Generate accurate pharmaceutical product data for: "{$productName}"

IMPORTANT INSTRUCTIONS:
- Extract the brand name directly from the product name (usually the first word before strength/form).
- Identify the REAL manufacturer by searching the web. Do NOT guess.
- The composition must be the ACTUAL active ingredient(s) for this specific product.
- Price in USD (realistic US pharmacy price).
- Category: Try to pick from [{$categories}]. If none fit, create a short, accurate new category (e.g., 'Skin Care', 'Diabetes', 'Cardiac').
- Brand MUST be one of: [{$brands}]. If no match, use the closest or the brand extracted from the product name.
- SKU format: MED-{first 3 letters of brand}-{strength numbers}, e.g., MED-MAL-100

Return this exact JSON structure:
{
"name":"{$productName}",
"short_description":"2-3 sentence accurate medical summary of this specific product",
"description":"<h2>About {$productName}</h2><p>overview</p><h3>Uses & Benefits</h3><ul><li>...</li></ul><h3>Dosage</h3><p>...</p><h3>Side Effects</h3><ul><li>...</li></ul><h3>Precautions</h3><ul><li>...</li></ul>",
"price":float_USD,
"compare_price":float_slightly_higher_than_price_USD,
"category":"matched or accurately created category",
"brand":"exact match from brand list above or extracted from product name",
"tags":["relevant","medical","tags"],
"composition":"Exact active ingredient(s) with strength (e.g., Sildenafil Citrate 100mg)",
"manufacturer":"Real manufacturer company name (NOT guessed)",
"storage_conditions":"Specific storage requirements",
"meta_title":"Buy {$productName} Online | Medinova Pharma",
"meta_description":"SEO description under 155 characters for {$productName}",
"unit":"strip|bottle|tube|box|sachet|vial (pick most appropriate)",
"weight":float_grams_realistic,
"sku":"MED-XXX-000",
"requires_prescription":true_or_false,
"is_featured":false
}
PROMPT;
    }

    // ── IMAGE GENERATION PIPELINE ──

    public function generateImage(string $productName): ?string
    {
        if (\App\Models\Setting::get('ai.generate_images', '1') !== '1') {
            return null;
        }

        set_time_limit(300);

        $slug    = Str::slug($productName);
        $tempDir = str_replace('\\', '/', storage_path('app/temp'));
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $refPath    = null;
        $rawOutput  = null;

        try {
            // ── STEP 1+2: Search & download reference image ──
            if (\App\Models\Setting::get('ai.enable_image_search', '1') === '1') {
                Log::info("Image pipeline: Searching reference for [{$productName}]");
                $refPath = app(ImageSearchService::class)
                    ->searchAndDownloadBest($productName, $slug);
            }

            // ── STEP 3+4+5: Edit with reference OR pure generation ──
            $editService = app(ImageEditService::class);

            if ($refPath) {
                Log::info("Image pipeline: Editing with reference for [{$productName}]");
                $rawOutput = $editService->editWithReference($refPath, $productName, $slug);
            }

            // Fallback: no reference or edit failed → locked-prompt generation
            if (!$rawOutput) {
                Log::info("Image pipeline: Falling back to locked-prompt generation for [{$productName}]");
                $rawOutput = $editService->generateWithLockedPrompt($productName, $slug);
            }

        } catch (\Exception $e) {
            Log::error("Image pipeline exception for [{$productName}]: " . $e->getMessage());
        } finally {
            // ── STEP 9: Always clean up reference temp file ──
            if ($refPath && file_exists($refPath)) {
                @unlink($refPath);
            }
        }

        if (!$rawOutput) {
            Log::warning("Image pipeline: All methods failed for [{$productName}], using placeholder");
            return $this->placeholderImage($productName);
        }

        Log::info("Image pipeline: SUCCESS for [{$productName}]", ['raw' => $rawOutput]);
        return $rawOutput;
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
