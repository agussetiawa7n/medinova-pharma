{{-- Product card — used on home, shop, related, wishlist. Bootstrap + pharma theme. --}}
@php
    $onSale    = $product->compare_price && $product->compare_price > $product->price;
    $discount  = $onSale ? (int) round((($product->compare_price - $product->price) / $product->compare_price) * 100) : 0;
    $isNew     = $product->created_at && $product->created_at->gt(now()->subDays(14));
    $outOfStock = $product->track_inventory && $product->stock_quantity <= 0 && !$product->allow_backorder;
    $rating    = round($product->reviews_avg_rating ?? $product->average_rating ?? 0, 1);
    $reviewCnt = $product->reviews_count ?? null;
    $inWishlist = $product->in_wishlist ?? false;
@endphp

<article class="mn-product-card" x-data="productCard({{ $product->id }})">

    {{-- Badges --}}
    <div class="mn-product-badges">
        @if($product->requires_prescription)
            <span class="mn-badge-rx">Rx</span>
        @endif
        @if($outOfStock)
            <span class="mn-badge-oos">Out of Stock</span>
        @elseif($onSale)
            <span class="mn-badge-sale">-{{ $discount }}%</span>
        @elseif($isNew)
            <span class="mn-badge-new">NEW</span>
        @endif
    </div>

    {{-- Quick actions (hover reveal) --}}
    <div class="mn-product-actions">
        @auth
            <button type="button" class="mn-product-action {{ $inWishlist ? 'active' : '' }}"
                    @click="toggleWishlist($event)"
                    title="{{ $inWishlist ? 'Remove from wishlist' : 'Add to wishlist' }}" aria-label="Toggle wishlist">
                <i class="{{ $inWishlist ? 'fa-solid' : 'fa-regular' }} fa-heart"></i>
            </button>
        @else
            <a href="{{ route('login') }}" class="mn-product-action" title="Sign in to use wishlist" aria-label="Wishlist">
                <i class="fa-regular fa-heart"></i>
            </a>
        @endauth
        <a href="{{ route('products.show', $product->slug) }}" class="mn-product-action" title="Quick view" aria-label="Quick view">
            <i class="fa-regular fa-eye"></i>
        </a>
    </div>

    {{-- Image --}}
    <a href="{{ route('products.show', $product->slug) }}" class="mn-product-media d-block">
        @if($product->thumbnail)
            <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" loading="lazy">
        @else
            <div class="w-100 h-100 d-flex align-items-center justify-content-center" style="color:#D1D5DB; font-size:48px;">
                <i class="fa-solid fa-pills"></i>
            </div>
        @endif
    </a>

    {{-- Body --}}
    <div class="mn-product-body">
        <div class="mn-product-category">{{ $product->brand?->name ?? $product->category?->name ?? 'Healthcare' }}</div>
        <h6 class="mn-product-name">
            <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
        </h6>

        @if($rating > 0)
            <div class="mn-product-rating">
                @for($i = 1; $i <= 5; $i++)
                    <i class="fa-{{ $i <= round($rating) ? 'solid' : 'regular' }} fa-star"></i>
                @endfor
                @if($reviewCnt !== null)<span class="cnt">({{ $reviewCnt }})</span>@endif
            </div>
        @endif

        <div class="mn-product-price">
            <span class="now">₹{{ number_format($product->price, 2) }}</span>
            @if($onSale)
                <span class="was">₹{{ number_format($product->compare_price, 2) }}</span>
            @endif
        </div>

        <div class="mn-product-cta">
            @if($outOfStock)
                <button type="button" class="btn btn-secondary" disabled>
                    <i class="fa-solid fa-ban me-1"></i> Out of Stock
                </button>
            @elseif($product->requires_prescription && !auth()->check())
                <a href="{{ route('login') }}" class="btn btn-pharma-outline">
                    <i class="fa-solid fa-prescription me-1"></i> Sign in to buy
                </a>
            @else
                <button type="button" class="btn btn-pharma"
                        @click="addToCart()" :disabled="loading">
                    <span x-show="!loading"><i class="fa-solid fa-cart-plus me-1"></i> Add to Cart</span>
                    <span x-show="loading" x-cloak><i class="fa-solid fa-spinner fa-spin me-1"></i> Adding…</span>
                </button>
            @endif
        </div>
    </div>
</article>
