# AI Product Pages — YMYL + EEAT + SEO Enhancement Plan

> Medinova Pharma · AI-generated product pages को Google के YMYL (Your Money Your Life) और
> EEAT (Experience, Expertise, Authoritativeness, Trustworthiness) standards के अनुसार बनाने का
> complete plan। यह document **implement होने से पहले** approval के लिए है।
>
> उदाहरण page: `/products/vidalista-5` (Tadalafil 5mg)

---

## 0. निर्णय (जो finalize हो चुके हैं)

| सवाल | निर्णय |
|---|---|
| Category कैसे handle हो | **Fixed whitelist** — AI सिर्फ़ मौजूदा active categories में से चुनेगा; match न हो तो `Needs Review`, कोई random category (जैसे "Sexual Wellness") auto-create नहीं होगी |
| EEAT byline | **"Reviewed by Medinova Medical Team"** (generic, कोई individual नाम नहीं) |
| पहला कदम | पहले यह लिखित plan, approve होने पर coding |

---

## 1. Root-cause Analysis (समस्या code में कहाँ है)

| # | समस्या | असली कारण | File |
|---|---|---|---|
| 1 | Weight **"15.00 kg"** गलत | DB में `weight` grams में है (`decimal(8,2)` comment: *in grams*) पर page पर hardcoded `kg` | `resources/views/products/show.blade.php:330` |
| 2 | **FAQ missing** | Product AI prompt के JSON schema में `faq` field ही नहीं | `app/Services/AIProductService.php:152` (`buildTextPrompt`) |
| 3 | **Medical disclaimer missing** | Category prompt में disclaimer है (line 144) पर Product prompt में नहीं; page पर भी कहीं render नहीं | `AIProductService.php:152` + `show.blade.php` |
| 4 | Category **खुद बन जाती है** | `Category::firstOrCreate(...)` — AI जो नाम दे नई category बन जाती है | `app/Filament/Pages/AIGenerateProducts.php:611` |
| 5 | Composition का **अलग page नहीं** | `composition` सिर्फ़ एक `string` column; कोई Composition model/route/page नहीं | `Product.php` / migrations |
| 6 | **SEO structured data नहीं** | show.blade में कोई JSON-LD (Product/Drug/FAQ/Breadcrumb) नहीं | `show.blade.php` |
| 7 | AI गलत जानकारी दे सकता है | कोई validation/human-review gate नहीं — AI का output सीधे publish | `AIGenerateProducts.php` (save flow) |

**अच्छी खबर:** weight का data ठीक है (grams), सिर्फ़ display label गलत है → fix आसान और safe।

---

## 2. Data Model बदलाव (Migrations)

### 2.1 `products` table में नए columns
```php
// database/migrations/xxxx_add_ai_medical_fields_to_products.php
$table->string('weight_unit', 10)->default('g')->after('weight');   // g | mg | ml | kg
$table->json('faq')->nullable()->after('composition');              // [{q, a}, ...]
$table->text('medical_disclaimer')->nullable()->after('faq');
$table->text('side_effects')->nullable()->after('medical_disclaimer');
$table->text('contraindications')->nullable();
$table->text('how_it_works')->nullable();
$table->string('drug_class')->nullable();
$table->foreignId('composition_id')->nullable()->after('category_id')
      ->constrained('compositions')->nullOnDelete();
$table->timestamp('content_reviewed_at')->nullable();               // "Last updated" EEAT
$table->string('content_status')->default('published');             // published | needs_review
```

### 2.2 नया `compositions` table (Salt pages के लिए)
```php
// database/migrations/xxxx_create_compositions_table.php
Schema::create('compositions', function (Blueprint $table) {
    $table->id();
    $table->string('name');              // e.g. "Tadalafil"
    $table->string('slug')->unique();    // tadalafil
    $table->text('overview')->nullable();
    $table->text('how_it_works')->nullable();
    $table->text('uses')->nullable();
    $table->text('side_effects')->nullable();
    $table->text('precautions')->nullable();
    $table->text('medical_disclaimer')->nullable();
    $table->string('meta_title')->nullable();
    $table->text('meta_description')->nullable();
    $table->string('content_status')->default('needs_review');
    $table->timestamp('content_reviewed_at')->nullable();
    $table->timestamps();
});
```

### 2.3 Model changes
- `Product.php`: `$fillable` + `casts` में नए fields (`faq => 'array'`), और `composition()` belongsTo relation।
- नया `Composition.php` model: `products()` hasMany + slug route-binding।

---

## 3. Phase-wise Plan (exact changes)

### फेज़ 1 — Quick Bug Fixes ⚡ (सबसे पहले, तुरंत असर)

**1A. Weight display fix** — `show.blade.php:330`
```blade
{{-- पहले --}}
@if($product->weight)<tr><th>Weight</th><td>{{ $product->weight }} kg</td></tr>@endif

{{-- बाद में --}}
@if($product->weight)
  <tr><th>Weight</th><td>{{ rtrim(rtrim(number_format($product->weight, 2), '0'), '.') }} {{ $product->weight_unit ?? 'g' }}</td></tr>
@endif
```

**1B. Prompt में weight unit साफ़ करना** — `AIProductService.php:186`
```
"weight": number (net weight in GRAMS of one saleable unit, e.g. a strip ≈ 5-20),
"weight_unit": "g",
```

**1C. Category whitelist enforcement** — `AIGenerateProducts.php:611`
```php
// firstOrCreate हटाओ → सिर्फ़ existing में match, वरना needs_review
$categoryId = null;
$catStatus  = 'published';
if (!empty($d['category'])) {
    $match = Category::whereRaw('LOWER(name) = ?', [strtolower($d['category'])])->first();
    if ($match) {
        $categoryId = $match->id;
    } else {
        $catStatus = 'needs_review';   // admin खुद assign करेगा
        Log::warning("AI ने unknown category दी: {$d['category']} (product: {$productName})");
    }
}
// Product::create([... 'content_status' => $catStatus ...])
```
- Prompt में भी सख़्त करना (`AIProductService.php:165`): *"Category MUST be EXACTLY one of [{$categories}]. यदि कोई fit न हो तो `category` को empty string छोड़ दो — नई category मत बनाओ।"*

---

### फेज़ 2 — YMYL Compliance 🩺 (medical safety + गलत info रोकना)

**2A. Product prompt को YMYL-hard करना** — `AIProductService.php` (`buildTextPrompt` + system prompt)
- System prompt में category-जैसे rules जोड़ो: evidence-based, no fabrication, "certain न हो तो field खाली छोड़ो", disclaimer अनिवार्य।
- JSON schema में नए fields:
```json
{
  "how_it_works": "<p>mechanism of action, plain + clinical</p>",
  "side_effects": "<ul><li>common</li><li>serious (seek help)</li></ul>",
  "contraindications": "<ul><li>कब न लें</li></ul>",
  "drug_class": "e.g. PDE5 inhibitor",
  "medical_disclaimer": "<div class='medical-disclaimer'>... consult a doctor ...</div>",
  "faq": [
    {"q": "What is {product} used for?", "a": "..."},
    {"q": "How to take {product}?", "a": "..."},
    {"q": "What are the side effects?", "a": "..."},
    {"q": "Is a prescription required?", "a": "..."},
    {"q": "Can I take it with alcohol?", "a": "..."}
  ]
}
```

**2B. Validation gate (सबसे ज़रूरी YMYL guard)** — नया `validateProductData(array $d): array` (AIProductService या नया `ProductDataValidator`)
- Checks: `price > 0`, `weight` 0.1–500g range में, `composition` non-empty, `category` whitelist में, FAQ ≥ 3।
- कोई भी check fail → `content_status = 'needs_review'`, product **draft** में जाए (auto-publish नहीं)।
- यही "AI galat information de" का असली safeguard है — **medical content publish होने से पहले human gate**।

**2C. Product page पर disclaimer + Rx warning** — `show.blade.php`
- Description tab के नीचे styled medical-disclaimer box (वही style जो category में `#ef4444` border के साथ है)।
- `requires_prescription` true पर prominent "⚠️ Prescription Required" badge।
- नया **FAQ section/tab** जो `$product->faq` render करे (accordion)।

**2D. `.medical-disclaimer` CSS** — global stylesheet में एक बार add (अभी inline style है)।

---

### फेज़ 3 — Composition (Salt) Pages 🧬 (auto-create + interlink)

**3A. Migration + Model** — section 2.2/2.3 के अनुसार।

**3B. Auto-link on save** — `AIGenerateProducts.php` (Product::create के आस-पास)
```php
$compositionId = null;
if (!empty($d['composition'])) {
    // "Tadalafil 5mg" → salt नाम "Tadalafil" निकालो (strength हटाओ)
    $saltName = trim(preg_replace('/\d+\s*(mg|mcg|ml|g|%).*/i', '', $d['composition']));
    if ($saltName !== '') {
        $comp = Composition::firstOrCreate(
            ['slug' => Str::slug($saltName)],
            ['name' => $saltName, 'content_status' => 'needs_review']
        );
        $compositionId = $comp->id;
        // अगर नया salt है → उसका content generate करने का job dispatch
        if ($comp->wasRecentlyCreated) {
            dispatch(new GenerateCompositionContentJob($comp->id));
        }
    }
}
// Product::create([... 'composition_id' => $compositionId ...])
```

**3C. Composition content generation** — नया method `AIProductService::generateCompositionDetails(string $salt)` (category-जैसा YMYL prompt) + नया `GenerateCompositionContentJob`।

**3D. Route + Controller + View**
- `routes/web.php`: `Route::get('/composition/{composition:slug}', [CompositionController::class, 'show'])->name('composition.show');`
- नया `CompositionController@show`: composition + उसके सभी active products।
- नया `resources/views/compositions/show.blade.php`: overview, mechanism, uses, side-effects, disclaimer + **"Products with Tadalafil" grid**।

**3E. Interlinking (SEO gold)**
- Product page पर composition को clickable link करो:
  `show.blade.php:285` → `<a href="{{ route('composition.show', $product->composition->slug) }}">{{ $product->composition->name }}</a>` (जब relation हो)।
- Composition page → उसके सभी products। इससे **topical authority + internal linking** मज़बूत होती है।

---

### फेज़ 4 — EEAT Signals ⭐ (Google trust)

Product + Composition page पर (एक reusable Blade partial `_eeat-meta.blade.php`):
- **"✔ Reviewed by Medinova Medical Team"** byline (निर्णय अनुसार generic team)।
- **"Last updated: {{ $product->content_reviewed_at?->format('d M Y') }}"** (fallback `updated_at`)।
- **References / Sources** section — एक छोटा static/AI-generated list (general pharmacology sources) ताकि content sourced दिखे।
- Trust badges (100% Genuine — पहले से है, बस structured heading के साथ)।

---

### फेज़ 5 — SEO Structured Data 🔍 (Schema.org JSON-LD)

`show.blade.php` (और composition view) के `@push('head')` में JSON-LD:

1. **Product + Offer**
```json
{ "@context":"https://schema.org", "@type":"Product",
  "name":"...", "image":"...", "description":"...",
  "sku":"...", "brand":{"@type":"Brand","name":"..."},
  "offers":{"@type":"Offer","price":"...","priceCurrency":"USD",
            "availability":"https://schema.org/InStock"} }
```
2. **Drug / MedicalWebPage** (YMYL pages के लिए Google-preferred) — activeIngredient, drugClass।
3. **FAQPage** — `$product->faq` से (rich results में FAQ दिखेगा)।
4. **BreadcrumbList** — Home › Category › Product।
5. **Meta hardening** — unique `meta_title`/`meta_description` (≤155 chars), `<link rel="canonical">`, OpenGraph/Twitter tags।

---

## 4. Roadmap (सुझाया गया क्रम)

```
Phase 1  Quick fixes        weight display + category whitelist + prompt unit   [~2 files]  ← सबसे पहले
Phase 2  YMYL compliance    prompt hardening + FAQ + disclaimer + validation    [migration + 3-4 files]
Phase 3  Composition pages  model + migration + job + route + view + interlink  [~6-7 files]  ← सबसे बड़ा
Phase 4  EEAT signals       byline + last-updated + references partial          [~2 files]
Phase 5  SEO schema         JSON-LD (Product/Drug/FAQ/Breadcrumb) + meta         [~1-2 files]
```

**Impact vs effort:** Phase 1 (bug + trust) और Phase 2 (medical safety) सबसे ज़्यादा value देंगे — इन्हें पहले करें। Phase 3 सबसे बड़ा effort (नया DB + pages)।

---

## 5. जोखिम / ध्यान देने योग्य

- **पुराने products** का weight पहले से grams में सही है → सिर्फ़ display fix से ठीक हो जाएँगे, data migration की ज़रूरत नहीं।
- Category whitelist लागू करने पर पहले से बनी गलत categories (जैसे "Sexual Wellness") DB में रहेंगी — इन्हें admin manually merge/rename करे।
- Validation gate से कुछ products `needs_review` में रुकेंगे — admin को उन्हें approve करने का workflow चाहिए (Filament में एक filter/action)।
- Composition salt-name parsing edge cases (combination drugs जैसे "Sildenafil + Dapoxetine") — इन्हें multiple salt links के रूप में handle करना होगा (Phase 3 में pivot table विकल्प)।

---

## 6. अगला कदम

यह plan approve करें, या किसी phase में बदलाव बताएँ। Approve होने पर मैं **Phase 1** से coding शुरू करूँगा और हर phase के बाद verify करके आगे बढ़ूँगा।
