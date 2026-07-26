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

    /**
     * Fallback disclaimer text used whenever the model returns an empty one.
     * YMYL rule: a product must NEVER go live without a disclaimer.
     */
    private const DEFAULT_DISCLAIMER = 'The information on this page is for general educational purposes only and is not a substitute for professional medical advice, diagnosis, or treatment. Always consult a qualified doctor or pharmacist before starting, stopping, or changing any medication.';

    /** Why the last generateImage() call produced nothing, for the caller to report. */
    private ?string $lastImageError = null;

    /**
     * How the last image was produced. The pipeline quietly falls back from
     * "edit the real product photo" to "invent packaging from a text prompt",
     * and the two look completely different to a customer — the admin needs to
     * be able to tell which one they are looking at.
     */
    private ?string $lastImageSource = null;

    public function lastImageError(): ?string
    {
        return $this->lastImageError;
    }

    public function lastImageSource(): ?string
    {
        return $this->lastImageSource;
    }

    /**
     * Inline-styled disclaimer block appended to the description HTML.
     * Inline styles (not just a class) so it stays visible in the admin
     * preview, exports and anywhere the site CSS is not loaded.
     */
    private function disclaimerHtml(string $text): string
    {
        $text = e(trim($text) ?: self::DEFAULT_DISCLAIMER);

        return '<div class="medical-disclaimer" style="margin-top:24px;padding:14px 18px;border-left:4px solid #ef4444;background:#fef2f2;color:#b91c1c;font-size:13px;line-height:1.6;border-radius:6px;">'
             . '<strong>Medical Disclaimer:</strong> ' . $text . '</div>';
    }

    /**
     * Safety net for the YMYL disclaimer rule. The prompt asks for it, but a
     * model can still drop it — so we guarantee it here instead of hoping.
     * Fills an empty medical_disclaimer and appends the styled block to the
     * description when the model did not include one.
     */
    private function ensureDisclaimer(array $data): array
    {
        $disclaimer = trim((string) ($data['medical_disclaimer'] ?? ''));
        if ($disclaimer === '') {
            $disclaimer = self::DEFAULT_DISCLAIMER;
            $data['medical_disclaimer'] = $disclaimer;
        }

        $description = (string) ($data['description'] ?? '');
        if ($description !== '' && !Str::contains($description, 'medical-disclaimer')) {
            $data['description'] = $description . $this->disclaimerHtml($disclaimer);
        }

        return $data;
    }

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

    /**
     * @param string|null $forcedCategory Category the admin pinned for this
     *        batch. When given, the model is told to use exactly that name
     *        instead of picking one from the whole list.
     */
    public function generateProductDetails(string $productName, ?string $forcedCategory = null): array
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
11. MANDATORY — every single product MUST have a non-empty medical_disclaimer. Do NOT write a disclaimer into the description; the application appends the approved, styled block itself so it is identical on every product.
12. Use correct clinical terminology alongside plain-language explanations so content is both authoritative and accessible.
13. Include a helpful, accurate faq with a minimum of 5 Q&A pairs, each answer 2-3 full sentences (not one-liners).

HTML QUALITY & PRESENTATION RULES (the description is rendered directly on the storefront):
14. The description must be rich, well-structured, scannable HTML — not a wall of plain text. Use <h2>/<h3> headings, short 2-3 sentence <p> paragraphs, <ul><li> bullets and a <table> of quick facts.
15. Emit PLAIN semantic HTML with NO style, class or id attributes. The storefront stylesheet already formats these tags. Inline CSS is pure wasted output and is the main reason a reply gets truncated before the JSON closes.
16. Aim for 450-700 words in the description. Substantial and genuinely useful — never padded with filler or repeated sentences.
17. NEVER put "Side Effects", "Precautions", "Contraindications" or "How it works / Mechanism of Action" sections inside the description. Those live in their own dedicated fields (side_effects, contraindications, how_it_works) and are rendered separately — repeating them in the description creates duplicate content that hurts SEO.
18. SEO: use the exact product name in the first sentence, in the first <h2>, and naturally 3-5 times overall. Write for humans first — no keyword stuffing.
19. Output valid HTML with properly closed tags. Escape "&" as "&amp;" inside text.
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
                ['role' => 'user',   'content' => $this->buildTextPrompt($productName, $forcedCategory)],
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('DeepSeek API error: ' . $response->body());
        }

        return $this->ensureDisclaimer(
            $this->parseJson($response->json('choices.0.message.content'))
        );
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

    private function buildTextPrompt(string $productName, ?string $forcedCategory = null): string
    {
        $brands = Brand::pluck('name')->implode(', ');

        // A pinned category is a decision the admin already made, so the model
        // is told the answer rather than asked to choose. Sending the full
        // category list in that case would only invite it to pick something
        // else — and it costs tokens the description needs.
        $forcedCategory = trim((string) $forcedCategory);

        if ($forcedCategory !== '') {
            $categoryRule = "- Category: this product has ALREADY been assigned to \"{$forcedCategory}\" by the store admin. "
                . "Return EXACTLY \"{$forcedCategory}\" in the category field — never a different or invented name. "
                . "Write the description so it reads naturally for a product in this category.";
        } else {
            $categories = Category::pluck('name')->implode(', ');
            $categoryRule = "- Category: MUST be EXACTLY one of these existing categories: [{$categories}]. "
                . 'Match the closest one. If truly none fit, return an EMPTY string "" for category — do NOT invent a new category name.';
        }

        $categoryJsonHint = $forcedCategory !== ''
            ? $forcedCategory
            : 'exact match from the category list above, or an empty string';

        return <<<PROMPT
Generate accurate pharmaceutical product data for: "{$productName}"

IMPORTANT INSTRUCTIONS:
- Extract the brand name directly from the product name (usually the first word before strength/form).
- Identify the REAL manufacturer by searching the web. Do NOT guess.
- The composition must be the ACTUAL active ingredient(s) for this specific product.
- Price in USD (realistic US pharmacy price).
{$categoryRule}
- Brand MUST be one of: [{$brands}]. If no match, use the closest or the brand extracted from the product name.
- SKU format: MED-{first 3 letters of brand}-{strength numbers}, e.g., MED-MAL-100

DESCRIPTION RULES (most important field — this is the page body customers read):
- Follow the HTML skeleton below section-for-section.
- Output PLAIN semantic HTML only. Do NOT add style, class or id attributes — the
  site stylesheet handles all appearance. Emitting inline CSS wastes the response
  budget and gets the reply cut off mid-JSON.
- Do NOT write a medical disclaimer here; the application appends the approved one.
- 450-700 words total, written by a clinical pharmacist for a patient: clear, calm, factual, no marketing hype.
- Do NOT add Side Effects / Precautions / Contraindications / Mechanism sections here — those are separate fields below.
- If a fact is unknown (e.g. manufacturer), write "Not specified" in the table rather than inventing it.

Return this exact JSON structure:
{
"name":"{$productName}",
"short_description":"2-3 sentence accurate medical summary of this specific product",
"description":"<h2>About {$productName}</h2><p>2-3 sentence opening naming {$productName}, its active ingredient with strength, its drug class and what it treats.</p><p>A second short paragraph on who it is typically prescribed for and what a patient can realistically expect.</p><h3>Quick Facts</h3><table><tbody><tr><th>Active Ingredient</th><td>salt with strength</td></tr><tr><th>Drug Class</th><td>pharmacological class</td></tr><tr><th>Manufacturer</th><td>real manufacturer</td></tr><tr><th>Form &amp; Pack</th><td>e.g. Tablet, strip of 10</td></tr><tr><th>Prescription</th><td>Required / Not required</td></tr></tbody></table><h3>Key Benefits</h3><ul><li><strong>Short benefit label</strong> — one clear supporting sentence.</li><li><strong>Second benefit</strong> — supporting sentence.</li><li><strong>Third benefit</strong> — supporting sentence.</li><li><strong>Fourth benefit</strong> — supporting sentence.</li></ul><h3>What {$productName} Is Used For</h3><p>One short lead-in sentence.</p><ul><li>primary approved indication</li><li>second indication</li><li>third indication</li></ul><h3>How to Take {$productName}</h3><p>General guidance on timing, food and water — educational only, never a personal dose recommendation.</p><ul><li>When to take it (with or without food, time of day)</li><li>How to swallow it (whole with water, do not crush/chew if applicable)</li><li>How long a typical course runs and why it should be completed</li></ul><h3>Missed Dose &amp; Overdose</h3><ul><li><strong>Missed dose:</strong> what to do, and the reminder never to double up.</li><li><strong>Overdose:</strong> advice to contact a doctor or emergency services immediately.</li></ul><h3>Storage &amp; Handling</h3><p>Specific storage temperature, light and moisture guidance, plus keeping it out of reach of children.</p>",
"price":float_USD,
"compare_price":float_slightly_higher_than_price_USD,
"category":"{$categoryJsonHint}",
"brand":"exact match from brand list above or extracted from product name",
"tags":["relevant","medical","tags"],
"composition":"Exact active ingredient(s) with strength (e.g., Sildenafil Citrate 100mg)",
"drug_class":"Pharmacological class (e.g., 'PDE5 inhibitor'). Empty string if unsure.",
"how_it_works":"<p>Plain-language explanation of what the medicine does in the body, followed by one sentence of precise clinical mechanism. Empty string if unsure.</p>",
"side_effects":"<p>Most people tolerate this medicine well. Side effects are usually mild and settle as the body adjusts.</p><h4>Common side effects</h4><ul><li>common effect — short note on managing it</li><li>second common effect</li><li>third common effect</li></ul><h4>Seek medical help immediately if you notice</h4><ul><li>serious effect needing urgent care</li><li>second serious effect</li></ul>",
"contraindications":"<ul><li><strong>Allergy:</strong> who must avoid it</li><li><strong>Pregnancy &amp; breastfeeding:</strong> accurate guidance</li><li><strong>Existing conditions:</strong> liver/kidney/cardiac cautions that apply</li><li><strong>Drug interactions:</strong> the key interacting medicines</li><li><strong>Alcohol:</strong> accurate guidance</li></ul>",
"medical_disclaimer":"A clear disclaimer telling the user this is educational only and to consult a qualified doctor/pharmacist before use. Plain text, 1-2 sentences. This field must NEVER be empty.",
"faq":[{"q":"What is {$productName} used for?","a":"2-3 full sentences."},{"q":"How should {$productName} be taken?","a":"General guidance only, and advise consulting a doctor. 2-3 sentences."},{"q":"What are the common side effects of {$productName}?","a":"2-3 sentences."},{"q":"Is a prescription required for {$productName}?","a":"2-3 sentences."},{"q":"How long does {$productName} take to work?","a":"2-3 sentences, or say it varies and depends on the condition being treated."},{"q":"Can {$productName} be taken with other medicines?","a":"2-3 sentences pointing to a doctor or pharmacist for an interaction check."}],
"manufacturer":"Real manufacturer company name (NOT guessed)",
"storage_conditions":"Specific storage requirements",
"meta_title":"Title of 50-60 characters that starts with {$productName}, adds its main use or strength, and ends with ' | Medinova Pharma'. Must NOT be empty.",
"meta_description":"Compelling 140-155 character SEO description that uses {$productName} in the first few words, states what it treats, and closes with a benefit such as genuine medicines or fast delivery. Must NOT be empty.",
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

    public function generateImage(string $productName, ?string $referenceUrl = null): ?string
    {
        if (\App\Models\Setting::get('ai.generate_images', '1') !== '1') {
            return null;
        }

        // No set_time_limit() here. The caller sets the ceiling with the host
        // limit in mind (280s against a 300s cap); raising it from inside the
        // pipeline only undid that.
        $slug    = Str::slug($productName);
        $tempDir = str_replace('\\', '/', storage_path('app/temp'));
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $refPath    = null;
        $rawOutput  = null;
        $searchNote = null;

        $this->lastImageError  = null;
        $this->lastImageSource = null;

        // Resolved once and reused: the container hands back a fresh instance on
        // every app() call, so asking a second one for lastError() would always
        // report null.
        $editService = app(ImageEditService::class);

        try {
            // ── STEP 1+2: Search & download reference image ──
            $search = app(ImageSearchService::class);

            if ($referenceUrl) {
                // An admin-supplied photo wins outright — no search, no guessing.
                Log::info("Image pipeline: Using supplied reference for [{$productName}]");
                $refPath = $search->downloadFrom($referenceUrl, $slug);

                $searchNote = $refPath
                    ? 'your reference: ' . ($search->lastSource() ?? 'supplied URL')
                    : ($search->lastError() ?? 'supplied reference could not be used');

            } elseif (\App\Models\Setting::get('ai.enable_image_search', '1') === '1') {
                Log::info("Image pipeline: Searching reference for [{$productName}]");
                $refPath = $search->searchAndDownloadBest($productName, $slug);

                $searchNote = $refPath
                    ? 'reference: ' . ($search->lastSource() ?? 'search result')
                    : ($search->lastError() ?? 'no reference image found');
            } else {
                $searchNote = 'image search disabled in AI Settings';
            }

            // ── STEP 3+4+5: Edit with reference OR pure generation ──
            if ($refPath) {
                Log::info("Image pipeline: Editing with reference for [{$productName}]");
                $rawOutput = $editService->editWithReference($refPath, $productName, $slug);

                if ($rawOutput) {
                    $this->lastImageSource = 'Edited from the real photo (' . $searchNote . ')';
                }
            }

            // Fallback: no reference or edit failed → locked-prompt generation.
            // This invents packaging from a text description; it is NOT the real
            // product, so the caller records it as such rather than letting it
            // pass for a photo.
            if (!$rawOutput) {
                Log::info("Image pipeline: Falling back to locked-prompt generation for [{$productName}]");
                $rawOutput = $editService->generateWithLockedPrompt($productName, $slug);

                if ($rawOutput) {
                    $this->lastImageSource = 'AI-invented packaging — ' . $searchNote;
                }
            }

        } catch (\Exception $e) {
            $this->lastImageError = $e->getMessage();
            Log::error("Image pipeline exception for [{$productName}]: " . $e->getMessage());
        } finally {
            // ── STEP 9: Always clean up reference temp file ──
            if ($refPath && file_exists($refPath)) {
                @unlink($refPath);
            }
        }

        if (!$rawOutput) {
            // Return null rather than a placeholder URL. The caller already has
            // its own placeholder fallback, and handing one back from here made
            // the failure indistinguishable from success — the queue row was
            // stamped with the real image model even though nothing was
            // generated, and the reason never reached the admin.
            $this->lastImageError ??= $editService->lastError()
                ?? 'Image search and AI generation both returned nothing. Check the Fal.ai key and credits in AI Settings.';

            Log::warning("Image pipeline: All methods failed for [{$productName}]: {$this->lastImageError}");

            return null;
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

        // The description is the page body — thin copy is a YMYL/SEO problem.
        $words = str_word_count(strip_tags((string) ($d['description'] ?? '')));
        if ($words < 200) {
            $issues[] = "Description is too thin ({$words} words, expected 200+).";
        }

        if (empty(trim($d['meta_title'] ?? ''))) {
            $issues[] = 'Meta title is missing.';
        }

        if (empty(trim($d['meta_description'] ?? ''))) {
            $issues[] = 'Meta description is missing.';
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

    private function parseJson(string $content): array
    {
        $json = $this->extractJsonBlock($content);

        // Attempt 1 — as returned.
        $data = json_decode($json, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }

        // Attempt 2 — escape raw control characters that sit INSIDE string values.
        // Models routinely emit literal newlines in long HTML fields, which is
        // invalid JSON. The previous code deleted them, which both corrupted the
        // text and failed to help, since a bare newline inside a string is only
        // legal once escaped — not once replaced by a space in the raw byte
        // stream that json_decode has already rejected.
        $data = json_decode($this->escapeControlCharsInStrings($json), true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }

        $error = json_last_error_msg();

        // A truncated response is the other common failure: the model ran into
        // max_tokens mid-object. Say so explicitly instead of leaving the admin
        // with a generic "Syntax error" they cannot act on.
        if (!str_ends_with(rtrim($json), '}')) {
            throw new \Exception(
                'AI response was cut off before the JSON finished (likely the max_tokens limit). '
                . 'Lower the amount of content requested or raise Max Tokens in AI Settings.'
            );
        }

        Log::error('AI JSON parse failed', ['error' => $error, 'head' => Str::limit($json, 400)]);

        throw new \Exception('Invalid JSON from AI: ' . $error);
    }

    /**
     * Pull the outermost {...} object out of a raw completion, discarding
     * markdown fences and any prose the model wrapped around it.
     */
    private function extractJsonBlock(string $content): string
    {
        $content = preg_replace('/^\s*```(?:json)?\s*/i', '', trim($content));
        $content = preg_replace('/\s*```\s*$/', '', $content);

        $start = strpos($content, '{');
        $end   = strrpos($content, '}');

        if ($start !== false && $end !== false && $end > $start) {
            return substr($content, $start, $end - $start + 1);
        }

        return trim($content);
    }

    /**
     * Walk the payload and escape control characters that appear inside string
     * literals, leaving the JSON structure itself untouched. Tracks quoting and
     * backslash escapes so a quote inside an already-escaped sequence does not
     * flip the in-string state.
     */
    private function escapeControlCharsInStrings(string $json): string
    {
        $out       = '';
        $inString  = false;
        $escaped   = false;
        $length    = strlen($json);

        for ($i = 0; $i < $length; $i++) {
            $char = $json[$i];

            if ($escaped) {
                $out .= $char;
                $escaped = false;
                continue;
            }

            if ($char === '\\' && $inString) {
                $out .= $char;
                $escaped = true;
                continue;
            }

            if ($char === '"') {
                $inString = !$inString;
                $out .= $char;
                continue;
            }

            if ($inString && ord($char) < 0x20) {
                $out .= match ($char) {
                    "\n"    => '\\n',
                    "\r"    => '\\r',
                    "\t"    => '\\t',
                    "\f"    => '\\f',
                    "\x08"  => '\\b',
                    default => sprintf('\\u%04x', ord($char)),
                };
                continue;
            }

            $out .= $char;
        }

        return $out;
    }
}
