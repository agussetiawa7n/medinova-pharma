@extends('layouts.app')

@section('title', $product->meta_title ?: $product->name)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($product->meta_description ?: ($product->short_description ?? $product->name)), 155))

@push('head')
@php
    $canonical  = route('products.show', $product->slug);
    $inStockLd  = $product->track_inventory ? (($product->stock_quantity > 0 || $product->allow_backorder) ? 'InStock' : 'OutOfStock') : 'InStock';
    $ldDesc     = \Illuminate\Support\Str::limit(trim(strip_tags($product->short_description ?: $product->description)), 300);

    $productLd = [
        '@context'    => 'https://schema.org/',
        '@type'       => 'Product',
        'name'        => $product->name,
        'image'       => [$product->thumbnail_url],
        'description' => $ldDesc,
        'sku'         => $product->sku,
        'category'    => optional($product->category)->name,
        'brand'       => ['@type' => 'Brand', 'name' => optional($product->brand)->name ?: 'Medinova Pharma'],
        'offers'      => [
            '@type'         => 'Offer',
            'url'           => $canonical,
            'priceCurrency' => 'USD',
            'price'         => number_format((float) $product->price, 2, '.', ''),
            'availability'  => 'https://schema.org/' . $inStockLd,
        ],
    ];
    if ($product->composition) {
        $productLd['activeIngredient'] = $product->composition;
    }
    $rc = (int) ($product->reviews_count ?? $product->reviews->count());
    if ($rc > 0) {
        $productLd['aggregateRating'] = [
            '@type'       => 'AggregateRating',
            'ratingValue' => (string) $product->average_rating,
            'reviewCount' => (string) $rc,
        ];
    }

    $crumbs = [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Shop', 'item' => route('products.index')],
    ];
    $pos = 3;
    if ($product->category) {
        $crumbs[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => $product->category->name, 'item' => route('products.index', ['category' => $product->category->slug])];
    }
    $crumbs[] = ['@type' => 'ListItem', 'position' => $pos, 'name' => $product->name, 'item' => $canonical];
    $breadcrumbLd = ['@context' => 'https://schema.org/', '@type' => 'BreadcrumbList', 'itemListElement' => $crumbs];

    $graph = [$productLd, $breadcrumbLd];

    if (!empty($product->faq) && is_array($product->faq)) {
        $qa = [];
        foreach ($product->faq as $f) {
            $q = is_array($f) ? ($f['q'] ?? '') : '';
            $a = is_array($f) ? ($f['a'] ?? '') : '';
            if ($q && $a) {
                $qa[] = ['@type' => 'Question', 'name' => $q, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a]];
            }
        }
        if ($qa) {
            $graph[] = ['@context' => 'https://schema.org/', '@type' => 'FAQPage', 'mainEntity' => $qa];
        }
    }
@endphp
<link rel="canonical" href="{{ $canonical }}">
<meta property="og:type" content="product">
<meta property="og:title" content="{{ $product->meta_title ?: $product->name }}">
<meta property="og:description" content="{{ $product->meta_description ? \Illuminate\Support\Str::limit(strip_tags($product->meta_description), 200) : $ldDesc }}">
<meta property="og:image" content="{{ $product->thumbnail_url }}">
<meta property="og:url" content="{{ $canonical }}">
@foreach($graph as $ld)
<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endforeach
@endpush

@section('breadcrumb')
    <ol>
        <li><a href="{{ route('home') }}"><i class="fa-solid fa-house me-1"></i> Home</a></li>
        <li class="sep">/</li>
        <li><a href="{{ route('products.index') }}">Shop</a></li>
        @if($product->category)
            <li class="sep">/</li>
            <li><a href="{{ route('products.index', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a></li>
        @endif
        <li class="sep">/</li>
        <li aria-current="page">{{ \Illuminate\Support\Str::limit($product->name, 50) }}</li>
    </ol>
@endsection

@section('content')
@php
    $onSale   = $product->compare_price && $product->compare_price > $product->price;
    $discount = $onSale ? (int) round((($product->compare_price - $product->price) / $product->compare_price) * 100) : 0;
    $stock    = (int) $product->stock_quantity;
    $inStock  = $product->track_inventory ? ($stock > 0 || $product->allow_backorder) : true;
    $images   = collect([$product->thumbnail])
        ->concat(is_array($product->images) ? $product->images : [])
        ->filter()
        ->unique()
        ->map(fn ($p) => \Illuminate\Support\Str::startsWith($p, ['http://','https://']) ? $p : \Illuminate\Support\Facades\Storage::disk('public')->url($p))
        ->values();
    $rating    = round($product->reviews->avg('rating') ?? 0, 1);
    $reviewCnt = $product->reviews_count ?? $product->reviews->count();
@endphp

<div class="container-xxl px-3 px-md-4 py-4 py-lg-5">

    <div class="row g-4 g-lg-5">

     {{-- ═════════ Gallery ═════════ --}}
<div class="col-lg-6" x-data="{ active: 0, images: @js($images->toArray()), fading: false,
    setActive(i) { if (this.active === i) return; this.fading = true; setTimeout(() => { this.active = i; this.fading = false; }, 200); }
}">
    <div class="bg-white border rounded-4 overflow-hidden mb-3 position-relative" style="aspect-ratio:1/1;">
        <template x-if="images.length">
            <img :src="images[active]" :alt="'{{ addslashes($product->name) }}'"
                 class="w-100 h-100"
                 :style="fading ? 'opacity:0;' : 'opacity:1;'"
                 style="object-fit:contain; padding:5%; transition:transform .4s ease, opacity .2s ease;"
                 @mouseover="$event.currentTarget.style.transform='scale(1.15)'"
                 @mouseleave="$event.currentTarget.style.transform=''">
        </template>
        <template x-if="!images.length">
            <div class="d-flex align-items-center justify-content-center h-100 text-muted" style="font-size:72px;">
                <i class="fa-solid fa-pills"></i>
            </div>
        </template>

        @if($onSale)
            <span class="position-absolute mn-badge-sale" style="top:20px; left:20px;">-{{ $discount }}%</span>
        @endif
        @if($product->requires_prescription)
            <span class="position-absolute mn-badge-rx" style="top:20px; right:20px;">
                <i class="fa-solid fa-prescription me-1"></i> Rx Required
            </span>
        @endif
    </div>

    {{-- Thumbnails --}}
    <template x-if="images.length > 1">
        <div class="d-flex gap-2 mn-thumb-row" style="overflow-x:auto; padding-bottom:4px; scrollbar-width:none;">
            <template x-for="(img, i) in images" :key="i">
                <button type="button"
                        class="btn p-0 border rounded-3 overflow-hidden"
                        style="width:78px; height:78px; background:#F8FAFB; flex-shrink:0;"
                        :style="active === i ? 'border-color:#e61f7f; border-width:2px;' : ''"
                        @click="setActive(i)">
                    <img :src="img" style="width:100%; height:100%; object-fit:cover;" alt="thumbnail">
                </button>
            </template>
        </div>
    </template>
</div>

        {{-- ═════════ Info ═════════ --}}
        <div class="col-lg-6" x-data="{
            quantity: 1,
            loading: false,
            added: false,
            selectedVariant: {{ $product->variants->where('is_default', true)->first()?->id ?? 'null' }},
            wishLoading: false,
            increment() { this.quantity = Math.min(this.quantity + 1, {{ max($stock, 1) }}); },
            decrement() { this.quantity = Math.max(this.quantity - 1, 1); },
            addToCart() {
                if (this.loading || this.added) return;

                const prevCount = Alpine.store('cart').count;
                const pendingId = 'p-' + Date.now();
                Alpine.store('cart').count = prevCount + this.quantity;
                this.loading = true;

                Alpine.store('cartPending').items.push({
                    id: pendingId, name: '{{ addslashes($product->name) }}',
                    price: {{ $product->price }}, image: '{{ addslashes($product->thumbnail_url) }}',
                    slug: '{{ $product->slug }}', quantity: this.quantity,
                    line: {{ $product->price }} * this.quantity,
                });

                window.openCartOffcanvas();

                window.apiFetch('/ajax/cart/add', {
                    method:'POST',
                    body: { product_id: {{ $product->id }}, quantity: this.quantity, variant_id: this.selectedVariant }
                })
                .then(data => {
                    Alpine.store('cart').count = data.count;
                    window.dispatchEvent(new CustomEvent('cart-updated'));
                    this.loading = false;
                    this.added = true;
                    setTimeout(() => { this.added = false; }, 2000);
                })
                .catch(err => {
                    Alpine.store('cart').count = prevCount;
                    Alpine.store('cartPending').items = Alpine.store('cartPending').items.filter(i => i.id !== pendingId);
                    Alpine.store('toast').add(err.message || 'Could not add to cart', 'error');
                    this.loading = false;
                });
            },
            toggleWishlist(el) {
                if (this.wishLoading) return;
                const btn = el; // stable reference for async .then()
                this.wishLoading = true;
                window.apiFetch('/ajax/wishlist/toggle', { method:'POST', body:{ product_id: {{ $product->id }} } })
                    .then(data => {
                        btn.classList.toggle('active', data.in_wishlist);
                        btn.querySelector('i').className = (data.in_wishlist ? 'fa-solid' : 'fa-regular') + ' fa-heart me-2';
                        window.dispatchEvent(new CustomEvent('wishlist-updated'));
                        Alpine.store('toast').add(data.message, 'success');
                    })
                    .catch(err => Alpine.store('toast').add(err.message || 'Please sign in', 'error'))
                    .finally(() => { this.wishLoading = false; });
            }
        }">
            @if($product->brand)
                <a href="{{ route('products.index', ['brand' => $product->brand->slug]) }}"
                   class="text-decoration-none" style="color:#e61f7f; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
                    {{ $product->brand->name }}
                </a>
            @endif

            <h1 class="mt-2 mb-2" style="font-size:clamp(22px, 3vw, 32px); font-weight:800; color:#303030; line-height:1.2;">
                {{ $product->name }}
            </h1>

            {{-- Rating + SKU --}}
            <div class="d-flex flex-wrap align-items-center gap-3 mb-3" style="font-size:13.5px; color:#6B7280;">
                @if($rating > 0)
                    <div style="color:#F6A609;">
                        @for($i=1; $i<=5; $i++)
                            <i class="fa-{{ $i <= round($rating) ? 'solid' : 'regular' }} fa-star"></i>
                        @endfor
                        <span class="ms-1" style="color:#303030; font-weight:600;">{{ $rating }}</span>
                        <span>({{ $reviewCnt }} review{{ $reviewCnt === 1 ? '' : 's' }})</span>
                    </div>
                    <span class="text-muted">|</span>
                @endif
                @if($product->sku)<span>SKU: <strong style="color:#303030;">{{ $product->sku }}</strong></span>@endif
                <span class="text-muted">|</span>
                <span>
                    @if($inStock)
                        <i class="fa-solid fa-circle-check" style="color:#e61f7f;"></i> In Stock
                    @else
                        <i class="fa-solid fa-circle-xmark" style="color:#DC3545;"></i> Out of Stock
                    @endif
                </span>
            </div>

            {{-- Price --}}
            <div class="d-flex align-items-baseline gap-3 mb-3 pb-3 border-bottom">
                <span style="font-size:36px; font-weight:800; color:#e61f7f;">${{ number_format($product->price, 2) }}</span>
                @if($onSale)
                    <span class="text-muted text-decoration-line-through" style="font-size:20px;">${{ number_format($product->compare_price, 2) }}</span>
                    <span class="mn-badge-sale">-{{ $discount }}% OFF</span>
                @endif
                <span class="ms-auto text-muted" style="font-size:12.5px;">Incl. all taxes</span>
            </div>

            @if($product->short_description)
                <p style="color:#636363; line-height:1.7; margin-bottom:20px;">{{ $product->short_description }}</p>
            @endif

            {{-- Prescription notice --}}
            @if($product->requires_prescription)
                <div class="p-3 mb-3 rounded-3 d-flex gap-3 align-items-start" style="background:#f2ecfa; border:1px solid #d5c9ed;">
                    <i class="fa-solid fa-prescription" style="color:#583fa8; font-size:22px; margin-top:2px;"></i>
                    <div style="color:#583fa8; font-size:14px;">
                        <strong>Prescription Required.</strong>
                        This medicine will be dispatched only after our pharmacist verifies a valid prescription from a registered medical practitioner.
                    </div>
                </div>
            @endif

            {{-- Variants --}}
            @if($product->variants->isNotEmpty())
                <div class="mb-3">
                    <label class="fw-bold mb-2" style="font-size:14px; color:#303030;">Choose variant</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($product->variants as $variant)
                            <button type="button" class="btn border px-3 py-2"
                                    style="border-radius:10px; font-size:14px;"
                                    :class="selectedVariant === {{ $variant->id }} ? 'border-success text-success fw-bold' : 'border-secondary-subtle text-secondary'"
                                    @click="selectedVariant = {{ $variant->id }}">
                                {{ $variant->name }}
                                @if($variant->price && $variant->price != $product->price)
                                    <span class="ms-1 text-muted">${{ number_format($variant->price, 2) }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Quantity + CTA --}}
            <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                <div class="mn-quantity">
                    <button type="button" @click="decrement()" :disabled="quantity <= 1">−</button>
                    <input type="text" x-model="quantity" readonly>
                    <button type="button" @click="increment()" :disabled="quantity >= {{ max($stock, 1) }}">+</button>
                </div>

                @if($inStock && ($product->requires_prescription && !auth()->check()))
                    <a href="{{ route('login') }}" class="btn btn-pharma flex-grow-1" style="padding:13px 24px;">
                        <i class="fa-solid fa-prescription me-2"></i> Sign in to buy
                    </a>
                @elseif($inStock)
                    <button type="button" class="btn btn-pharma flex-grow-1" style="padding:13px 24px;"
                            @click="addToCart()" :disabled="loading || added"
                            :class="{ 'bg-success border-success': added }">
                        <span>
                            <i class="fa-solid fa-cart-plus me-2"
                               :class="added ? 'fa-check' : loading ? 'fa-spinner fa-spin' : 'fa-cart-plus'"></i>
                            <span x-text="added ? 'Added ✓' : loading ? 'Adding…' : 'Add to Cart'">Add to Cart</span>
                        </span>
                    </button>
                @else
                    <button type="button" class="btn btn-secondary flex-grow-1" style="padding:13px 24px;" disabled>
                        <i class="fa-solid fa-ban me-2"></i> Out of Stock
                    </button>
                @endif

                @auth
                    <button type="button" class="btn btn-pharma-outline" style="padding:12px 22px;"
                            @click="toggleWishlist($event.currentTarget)" :disabled="wishLoading">
                        <i class="fa-regular fa-heart me-2"></i> Wishlist
                    </button>
                @else
                    <a href="{{ route('login') }}" class="btn btn-pharma-outline" style="padding:12px 22px;">
                        <i class="fa-regular fa-heart me-2"></i> Wishlist
                    </a>
                @endauth
            </div>

            {{-- Trust badges --}}
            <div class="d-flex flex-wrap gap-3 py-3 border-top border-bottom mb-4">
                <div class="d-flex align-items-center gap-2" style="font-size:13px; color:#636363;">
                    <i class="fa-solid fa-truck-fast" style="color:#e61f7f; font-size:18px;"></i> Free delivery above ${{ setting('pricing.free_shipping_threshold', 499) }}
                </div>
                <div class="d-flex align-items-center gap-2" style="font-size:13px; color:#636363;">
                    <i class="fa-solid fa-shield-halved" style="color:#e61f7f; font-size:18px;"></i> 100% Genuine
                </div>
                <div class="d-flex align-items-center gap-2" style="font-size:13px; color:#636363;">
                    <i class="fa-solid fa-rotate-left" style="color:#e61f7f; font-size:18px;"></i> 7-day returns
                </div>
            </div>

            {{-- Prescription warning (YMYL) --}}
            @if($product->requires_prescription)
                <div class="d-flex align-items-start gap-2 p-3 mb-4 rounded-3"
                     style="background:#FEF3C7; border-left:4px solid #F59E0B; color:#92400E; font-size:13.5px;">
                    <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                    <span><strong>Prescription required.</strong> This medicine should only be used under the guidance of a registered medical practitioner. A valid prescription may be required at checkout.</span>
                </div>
            @endif

            {{-- Meta info --}}
            <ul class="list-unstyled small text-muted mb-0" style="font-size:13.5px;">
                @if($product->category)
                    <li class="mb-1"><strong class="text-dark">Category:</strong> <a href="{{ route('products.index', ['category' => $product->category->slug]) }}" style="color:#e61f7f; text-decoration:none;">{{ $product->category->name }}</a></li>
                @endif
                @if($product->manufacturer)
                    <li class="mb-1"><strong class="text-dark">Manufacturer:</strong> {{ $product->manufacturer }}</li>
                @endif
                @if($product->composition)
                    <li class="mb-1"><strong class="text-dark">Composition:</strong>
                        @if($product->compositionModel)
                            <a href="{{ route('composition.show', $product->compositionModel->slug) }}" style="color:#e61f7f; text-decoration:none;">{{ $product->composition }}</a>
                        @else
                            {{ $product->composition }}
                        @endif
                    </li>
                @endif
                @if(is_array($product->tags) && count($product->tags))
                    <li><strong class="text-dark">Tags:</strong> {{ implode(', ', $product->tags) }}</li>
                @endif
            </ul>
        </div>
    </div>

    {{-- ═════════ Tabs ═════════ --}}
    <div class="mt-5 pt-4 border-top">
        <ul class="nav nav-tabs" id="mnProductTabs" role="tablist" style="border-bottom:2px solid #EDEFF2;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold" id="tab-desc" data-bs-toggle="tab" data-bs-target="#pane-desc" type="button" role="tab">Description</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="tab-details" data-bs-toggle="tab" data-bs-target="#pane-details" type="button" role="tab">Additional Info</button>
            </li>
            @if(!empty($product->faq))
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="tab-faq" data-bs-toggle="tab" data-bs-target="#pane-faq" type="button" role="tab">FAQ</button>
            </li>
            @endif
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="tab-reviews" data-bs-toggle="tab" data-bs-target="#pane-reviews" type="button" role="tab">Reviews ({{ $reviewCnt }})</button>
            </li>
        </ul>

        <div class="tab-content py-4">
            <div class="tab-pane fade show active" id="pane-desc" role="tabpanel">
                <div class="mn-prose" style="max-width:900px; line-height:1.8; color:#636363;">
                    @php
                        $allowedTags = '<h1><h2><h3><h4><h5><h6><p><ul><ol><li><a><strong><b><em><i><br><hr><blockquote><span><div><table><thead><tbody><tr><th><td><img><sup><sub><code><pre>';
                        // AI descriptions end with their own disclaimer block. The page
                        // already renders one below the tabs, so drop the inline copy —
                        // otherwise it lands mid-tab, above "How it works".
                        $bodyHtml = preg_replace(
                            '/<div[^>]*class="[^"]*medical-disclaimer[^"]*"[^>]*>.*?<\/div>/is',
                            '',
                            (string) $product->description
                        );
                    @endphp
                    @if(trim(strip_tags($bodyHtml)) !== '')
                        {!! \App\Support\HtmlSanitizer::clean($bodyHtml) !!}
                    @else
                        <p class="text-muted">No detailed description available.</p>
                    @endif

                    @if($product->how_it_works)
                        <h3 class="mt-4">How it works</h3>
                        {!! \App\Support\HtmlSanitizer::clean($product->how_it_works) !!}
                    @endif
                    @if($product->side_effects)
                        <h3 class="mt-4">Side effects</h3>
                        {!! \App\Support\HtmlSanitizer::clean($product->side_effects) !!}
                    @endif
                    @if($product->contraindications)
                        <h3 class="mt-4">Who should not take this</h3>
                        {!! \App\Support\HtmlSanitizer::clean($product->contraindications) !!}
                    @endif
                </div>
            </div>

            <div class="tab-pane fade" id="pane-details" role="tabpanel">
                <div class="table-responsive" style="max-width:720px;">
                    <table class="table table-bordered">
                        <tbody>
                            @if($product->sku)<tr><th style="width:180px;">SKU</th><td>{{ $product->sku }}</td></tr>@endif
                            @if($product->brand)<tr><th>Brand</th><td>{{ $product->brand->name }}</td></tr>@endif
                            @if($product->category)<tr><th>Category</th><td>{{ $product->category->name }}</td></tr>@endif
                            @if($product->manufacturer)<tr><th>Manufacturer</th><td>{{ $product->manufacturer }}</td></tr>@endif
                            @if($product->composition)<tr><th>Composition</th><td>@if($product->compositionModel)<a href="{{ route('composition.show', $product->compositionModel->slug) }}" style="color:#e61f7f; text-decoration:none;">{{ $product->composition }}</a>@else{{ $product->composition }}@endif</td></tr>@endif
                            @if($product->storage_conditions)<tr><th>Storage</th><td>{{ $product->storage_conditions }}</td></tr>@endif
                            @if($product->expiry_date)<tr><th>Expiry</th><td>{{ $product->expiry_date->format('M Y') }}</td></tr>@endif
                            @if($product->weight)<tr><th>Weight</th><td>{{ rtrim(rtrim(number_format($product->weight, 2), '0'), '.') }} {{ $product->weight_unit ?? 'g' }}</td></tr>@endif
                            <tr><th>Prescription</th><td>{{ $product->requires_prescription ? 'Required' : 'Not required' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            @if(!empty($product->faq))
            <div class="tab-pane fade" id="pane-faq" role="tabpanel">
                <div class="accordion mn-faq" id="faqAccordion" style="max-width:820px;">
                    @foreach($product->faq as $i => $f)
                        @php $q = is_array($f) ? ($f['q'] ?? '') : ''; $a = is_array($f) ? ($f['a'] ?? '') : ''; @endphp
                        @if($q && $a)
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqHead{{ $i }}">
                                <button class="accordion-button {{ $i === 0 ? '' : 'collapsed' }}" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faqBody{{ $i }}"
                                        aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="faqBody{{ $i }}">
                                    {{ $q }}
                                </button>
                            </h3>
                            <div id="faqBody{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                                 aria-labelledby="faqHead{{ $i }}" data-bs-parent="#faqAccordion">
                                <div class="accordion-body" style="color:#636363; font-size:14.5px; line-height:1.7;">
                                    {{ $a }}
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            <div class="tab-pane fade" id="pane-reviews" role="tabpanel">
                @if($product->reviews->isEmpty())
                    <div class="text-center py-5">
                        <i class="fa-regular fa-comments" style="font-size:48px; color:#D1D5DB;"></i>
                        <p class="text-muted mt-3 mb-0">No reviews yet. Be the first to review this product!</p>
                    </div>
                @else
                    <div class="row g-3" style="max-width:900px;">
                        @foreach($product->reviews as $review)
                            <div class="col-12">
                                <div class="border rounded-3 p-3 p-md-4" style="background:#F8FAFB;">
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <div style="width:42px; height:42px; border-radius:50%; background:linear-gradient(135deg,#e61f7f,#b81964); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800;">
                                            {{ strtoupper(substr($review->user->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="m-0 fw-bold" style="color:#303030;">{{ $review->user->name ?? 'Anonymous' }}</p>
                                            <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                                        </div>
                                        <div class="ms-auto" style="color:#F6A609;">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fa-{{ $i <= $review->rating ? 'solid' : 'regular' }} fa-star"></i>
                                            @endfor
                                        </div>
                                    </div>
                                    @if($review->comment)
                                        <p class="mb-0 text-muted" style="font-size:14.5px;">{{ $review->comment }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═════════ Medical disclaimer (YMYL) ═════════ --}}
    <div class="medical-disclaimer mt-4" role="note">
        <strong><i class="fa-solid fa-circle-info me-1"></i> Medical Disclaimer:</strong>
        {{ $product->medical_disclaimer
            ?: 'The information on this page is for general educational purposes only and is not a substitute for professional medical advice, diagnosis, or treatment. Always consult a qualified doctor or pharmacist before starting, stopping, or changing any medication.' }}
    </div>

    @include('partials._eeat', ['updated' => $product->updated_at])

    {{-- ═════════ Related ═════════ --}}
    @if($related->isNotEmpty())
        <section class="mt-5 pt-4">
            <div class="mn-section-head">
                <div>
                    <p class="mn-eyebrow">You may also like</p>
                    <h2>Related products</h2>
                </div>
            </div>
            <div class="swiper mn-related-swiper">
                <div class="swiper-wrapper">
                    @foreach($related as $product)
                        <div class="swiper-slide" style="height:auto;">
                            @include('products._card', compact('product'))
                        </div>
                    @endforeach
                </div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </section>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.mn-related-swiper').forEach(el => {
            new Swiper(el, {
                slidesPerView: 2, spaceBetween: 16,
                navigation: { prevEl: el.querySelector('.swiper-button-prev'), nextEl: el.querySelector('.swiper-button-next') },
                breakpoints: {
                    576: { slidesPerView: 2, spaceBetween: 16 },
                    768: { slidesPerView: 3, spaceBetween: 20 },
                    1200: { slidesPerView: 4, spaceBetween: 24 },
                },
            });
        });
    });
</script>
<style>
    .mn-related-swiper { padding: 8px 8px 40px; margin: 0 -8px; }
    .mn-related-swiper .swiper-button-prev,
    .mn-related-swiper .swiper-button-next {
        width:40px; height:40px; background:#fff; border-radius:50%;
        box-shadow:0 6px 16px rgba(15,23,42,0.10); color:#e61f7f;
        top: calc(50% - 24px);
    }
    .mn-related-swiper .swiper-button-prev::after,
    .mn-related-swiper .swiper-button-next::after { font-size:16px; font-weight:800; }

    #mnProductTabs .nav-link { color:#6B7280; border:0; padding:12px 24px; }
    #mnProductTabs .nav-link.active { color:#e61f7f; background:transparent; border-bottom:3px solid #e61f7f; margin-bottom:-2px; }

    .mn-thumb-row::-webkit-scrollbar { display: none; }

    .medical-disclaimer {
        padding: 15px 18px; border-left: 4px solid #ef4444;
        background-color: #fef2f2; color: #b91c1c;
        font-size: 13px; line-height: 1.6; border-radius: 6px;
    }

    /* ── Rich product copy (AI-generated or hand-written) ── */
    .mn-prose h2 {
        font-size: 22px; font-weight: 800; color: #303030;
        margin: 0 0 14px; padding-bottom: 10px; border-bottom: 2px solid #f3f4f6;
    }
    .mn-prose h3 {
        font-size: 18px; font-weight: 700; color: #303030;
        margin: 28px 0 12px; padding-left: 12px; border-left: 4px solid #e61f7f;
    }
    .mn-prose h4 { font-size: 15.5px; font-weight: 700; color: #4b5563; margin: 18px 0 8px; }
    .mn-prose p  { margin-bottom: 14px; font-size: 15px; }
    .mn-prose ul, .mn-prose ol { padding-left: 0; margin-bottom: 16px; list-style: none; }
    .mn-prose ol { counter-reset: mn-step; }
    .mn-prose li { position: relative; padding-left: 26px; margin-bottom: 9px; font-size: 15px; }
    .mn-prose ul > li::before {
        content: ''; position: absolute; left: 4px; top: 11px;
        width: 7px; height: 7px; border-radius: 50%; background: #e61f7f;
    }
    .mn-prose ol > li::before {
        counter-increment: mn-step; content: counter(mn-step) '.';
        position: absolute; left: 0; top: 0; color: #e61f7f; font-weight: 700;
    }
    .mn-prose li strong { color: #303030; }
    .mn-prose table { width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 14.5px; }
    .mn-prose th, .mn-prose td { padding: 10px 14px; border: 1px solid #e5e7eb; text-align: left; }
    .mn-prose th { background: #f8fafb; font-weight: 600; color: #303030; }
    .mn-prose a { color: #e61f7f; text-decoration: none; }
    .mn-prose a:hover { text-decoration: underline; }
    .mn-prose > *:first-child { margin-top: 0; }
    @media (max-width: 575.98px) {
        .mn-prose h2 { font-size: 19px; }
        .mn-prose h3 { font-size: 16.5px; }
        .mn-prose table { font-size: 13.5px; }
        .mn-prose th, .mn-prose td { padding: 8px 10px; }
    }
    .mn-faq .accordion-button:not(.collapsed) { color:#e61f7f; background:#fff5fa; box-shadow:none; }
    .mn-faq .accordion-button:focus { box-shadow:none; border-color:#f3c6dc; }
    .mn-faq .accordion-button { font-weight:600; color:#303030; }
</style>
@endpush
@endsection