<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AIProductService
{
    // DeepSeek API
    private const BASE_URL = 'https://api.deepseek.com';

    private function apiKey(): string
    {
        return \App\Models\Setting::get('ai.deepseek_api_key', config('services.deepseek.api_key', ''));
    }

    // ── LIGHTWEIGHT PING ──

    public function ping(): array
    {
        $response = Http::connectTimeout(5)->timeout(8)->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey(),
            'Content-Type'  => 'application/json',
        ])->post(self::BASE_URL . '/chat/completions', [
            'model'       => \App\Models\Setting::get('ai.text_model', 'deepseek-v4-pro'),
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
        set_time_limit(120);

        $model     = \App\Models\Setting::get('ai.text_model', 'deepseek-v4-pro');
        $temp      = (float) (\App\Models\Setting::get('ai.temperature', '0.3'));
        $maxTokens = (int) (\App\Models\Setting::get('ai.max_tokens', '8000'));

        $systemPrompt = <<<'SYS'
You are a pharmaceutical product data specialist and clinical medical writer with deep knowledge of Indian and international pharma brands, generics, and OTC products.

CRITICAL ACCURACY RULES — FOLLOW STRICTLY:
1. NEVER fabricate or guess manufacturer, brand, or composition. If you are not 100% certain, SEARCH THE WEB or write "Unknown".
2. The product name itself often contains the brand name and strength — extract data FROM the name first.
3. For Indian pharma products: carefully identify the REAL manufacturer (e.g., Healing Pharma, Sunrise Remedies, Cipla, Sun Pharma, Mankind, etc.). Do NOT default to Cipla or any big brand unless you are certain.
4. The brand is usually the FIRST word(s) in the product name before the strength/dosage form (e.g., "Malegra Oral Jelly" → brand is "Malegra", NOT "Cipla").
5. Composition must match the ACTUAL active ingredient for that specific branded product.
6. Prices should be realistic US wholesale/retail pharmacy prices in USD.
7. Return ONLY valid JSON. No markdown, no backticks, no explanation text.

YMYL & EEAT MEDICAL-CONTENT RULES (this is health content — accuracy protects real patients):
8. All medical content MUST be evidence-based, objective, and unbiased. No marketing hype, no exaggerated efficacy claims, no "miracle" language.
9. If you are not certain about a clinical fact (side effect, contraindication, mechanism), OMIT it or return an empty value — do NOT invent it. An empty field is far safer than a wrong one.
10. Never give individual dosing/medical advice; write at a general, educational level and always point the reader to a qualified doctor.
11. ALWAYS include a clear medical_disclaimer and a helpful, accurate faq (minimum 4 Q&A) for the product.
12. Use correct clinical terminology alongside plain-language explanations so content is both authoritative and accessible.
SYS;

        $response = Http::connectTimeout(10)->timeout(90)->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey(),
            'Content-Type'  => 'application/json',
        ])->post(self::BASE_URL . '/chat/completions', [
            'model'           => $model,
            'temperature'     => $temp,
            'max_tokens'      => $maxTokens,
            'response_format' => ['type' => 'json_object'],
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $this->buildTextPrompt($productName)],
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('DeepSeek API error: ' . $response->body());
        }

        return $this->parseJson($response->json('choices.0.message.content'));
    }

    public function generateCategoryDetails(string $categoryName): array
    {
        set_time_limit(120);

        $model     = \App\Models\Setting::get('ai.text_model', 'deepseek-v4-pro');
        $temp      = (float) (\App\Models\Setting::get('ai.temperature', '0.3'));
        $maxTokens = (int) (\App\Models\Setting::get('ai.max_tokens', '8000'));

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

        $response = Http::connectTimeout(10)->timeout(90)->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey(),
            'Content-Type'  => 'application/json',
        ])->post(self::BASE_URL . '/chat/completions', [
            'model'           => $model,
            'temperature'     => $temp,
            'max_tokens'      => $maxTokens,
            'response_format' => ['type' => 'json_object'],
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $this->buildCategoryTextPrompt($categoryName)],
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('DeepSeek API error: ' . $response->body());
        }

        return $this->parseJson($response->json('choices.0.message.content'));
    }

    public function generateCompositionDetails(string $saltName): array
    {
        set_time_limit(120);

        $model     = \App\Models\Setting::get('ai.text_model', 'deepseek-v4-pro');
        $temp      = (float) (\App\Models\Setting::get('ai.temperature', '0.3'));
        $maxTokens = (int) (\App\Models\Setting::get('ai.max_tokens', '8000'));

        $systemPrompt = <<<'SYS'
You are an expert clinical pharmacist and pharmaceutical medical writer.
You generate authoritative, trustworthy, evidence-based content about drug active ingredients (salts) for a pharmacy website.

CRITICAL COMPLIANCE RULES — YMYL & EEAT:
1. This is health content that affects real patients. Every clinical fact MUST be accurate and evidence-based. If you are not certain about a fact, OMIT it or return an empty value — never invent it.
2. Write at a general, educational level. Do NOT give individual dosing or personal medical advice.
3. Use correct clinical terminology alongside plain-language explanations so it is both authoritative and accessible.
4. Always include clear precautions and a prominent medical disclaimer telling users to consult a qualified doctor or pharmacist.
5. Return ONLY valid JSON. No markdown, no backticks, no explanation text.
SYS;

        $response = Http::connectTimeout(10)->timeout(90)->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey(),
            'Content-Type'  => 'application/json',
        ])->post(self::BASE_URL . '/chat/completions', [
            'model'           => $model,
            'temperature'     => $temp,
            'max_tokens'      => $maxTokens,
            'response_format' => ['type' => 'json_object'],
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $this->buildCompositionTextPrompt($saltName)],
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('DeepSeek API error: ' . $response->body());
        }

        return $this->parseJson($response->json('choices.0.message.content'));
    }

    private function buildCompositionTextPrompt(string $saltName): string
    {
        return <<<PROMPT
Generate accurate, YMYL-compliant and EEAT-rich medical content for the drug active ingredient (salt): "{$saltName}"

Return this exact JSON structure (each HTML value is a small block; use empty string if you are not certain):
{
"name":"{$saltName}",
"overview":"<p>What {$saltName} is, its drug class, and what it is broadly used for.</p>",
"how_it_works":"<p>Plain-language + clinical mechanism of action of {$saltName}.</p>",
"uses":"<ul><li>main approved/common uses</li></ul>",
"side_effects":"<ul><li>common side effects</li><li>serious effects needing urgent medical help</li></ul>",
"precautions":"<ul><li>key precautions, interactions and who should avoid it</li></ul>",
"medical_disclaimer":"A clear 1-2 sentence disclaimer that this is educational only and the reader must consult a qualified doctor or pharmacist before use.",
"meta_title":"{$saltName}: Uses, Side Effects & Precautions | Medinova Pharma",
"meta_description":"SEO description under 155 characters about {$saltName} — uses, how it works, side effects and safety."
}
PROMPT;
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
- Category: MUST be EXACTLY one of these existing categories: [{$categories}]. Match the closest one. If truly none fit, return an EMPTY string "" for category — do NOT invent a new category name.
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
"drug_class":"Pharmacological class (e.g., 'PDE5 inhibitor'). Empty string if unsure.",
"how_it_works":"<p>Plain-language + clinical explanation of the mechanism of action. Empty string if unsure.</p>",
"side_effects":"<ul><li>common side effects</li><li>serious effects that need urgent medical help</li></ul>",
"contraindications":"<ul><li>who should NOT take this / when to avoid it</li></ul>",
"medical_disclaimer":"A clear disclaimer telling the user this is educational only and to consult a qualified doctor/pharmacist before use. Plain text, 1-2 sentences.",
"faq":[{"q":"What is {$productName} used for?","a":"..."},{"q":"How should {$productName} be taken?","a":"General guidance only, advise consulting a doctor."},{"q":"What are the common side effects?","a":"..."},{"q":"Is a prescription required for {$productName}?","a":"..."}],
"manufacturer":"Real manufacturer company name (NOT guessed)",
"storage_conditions":"Specific storage requirements",
"meta_title":"Buy {$productName} Online | Medinova Pharma",
"meta_description":"SEO description under 155 characters for {$productName}",
"unit":"strip|bottle|tube|box|sachet|vial (pick most appropriate)",
"weight":number (net weight of ONE saleable unit as a plain number, e.g. a strip of tablets is usually 5-20),
"weight_unit":"g|ml|mg (use 'g' for tablets/strips/capsules, 'ml' for syrups/liquids)",
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

    // ── YMYL VALIDATION GATE ──

    /**
     * Validate AI-generated product data before it goes live.
     * Returns a list of human-readable issues. An EMPTY list means the data is
     * safe to auto-publish; a non-empty list means it must be held for human review.
     *
     * @param array $d        The AI-generated product data.
     * @param bool  $matchedCategory Whether the category matched an existing one.
     */
    public function validateProductData(array $d, bool $matchedCategory): array
    {
        $issues = [];

        $price = (float) ($d['price'] ?? 0);
        if ($price <= 0) {
            $issues[] = 'Price is missing or not greater than 0.';
        }

        // weight is stored in grams — sane pharma range is ~0.05g to 2000g
        if (isset($d['weight']) && $d['weight'] !== null && $d['weight'] !== '') {
            $w = (float) $d['weight'];
            if ($w < 0.05 || $w > 2000) {
                $issues[] = "Weight {$w} is outside the sane range (0.05–2000).";
            }
        } else {
            $issues[] = 'Weight is missing.';
        }

        if (empty(trim($d['composition'] ?? ''))) {
            $issues[] = 'Composition (active ingredient) is missing.';
        }

        if (!$matchedCategory) {
            $issues[] = 'Category did not match any existing category.';
        }

        $faq = $d['faq'] ?? [];
        if (!is_array($faq) || count($faq) < 3) {
            $issues[] = 'Fewer than 3 FAQ entries.';
        }

        if (empty(trim($d['medical_disclaimer'] ?? ''))) {
            $issues[] = 'Medical disclaimer is missing.';
        }

        return $issues;
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

        // The model sometimes emits raw control characters (literal newlines/tabs
        // inside HTML string values), which is invalid JSON. Strip control chars
        // — 0x00–0x1F excluding nothing structural is safe here — and retry once.
        if (json_last_error() !== JSON_ERROR_NONE) {
            $sanitized = preg_replace('/[\x00-\x1F]+/', ' ', $content);
            $data = json_decode($sanitized, true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON from AI: ' . json_last_error_msg());
        }

        return $data;
    }
}
