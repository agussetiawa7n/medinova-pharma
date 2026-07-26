@extends('layouts.app')

@section('title', $activeCategory && $activeCategory->meta_title
    ? $activeCategory->meta_title
    : ($activeCategory->name ?? 'Shop All Products'))

@if($activeCategory && $activeCategory->meta_description)
    @section('meta_description', $activeCategory->meta_description)
@elseif($activeCategory && $activeCategory->description)
    @section('meta_description', \Illuminate\Support\Str::limit(strip_tags($activeCategory->description), 155))
@endif

@section('breadcrumb')
    <ol>
        <li><a href="{{ route('home') }}"><i class="fa-solid fa-house me-1"></i> Home</a></li>
        <li class="sep">/</li>
        <li aria-current="page">Shop</li>
        @if($activeCategory)
            <li class="sep">/</li>
            <li aria-current="page">{{ $activeCategory->name }}</li>
        @endif
    </ol>
@endsection

@section('content')
<div class="container py-4 py-lg-5">
    <div class="row g-4 g-lg-5">

        {{-- ═════════ SIDEBAR FILTERS ═════════ --}}
        <aside class="col-lg-3">

            {{-- Mobile toggle --}}
            <button class="btn btn-pharma-outline w-100 d-lg-none mb-3" type="button"
                    data-bs-toggle="collapse" data-bs-target="#mnFilters">
                <i class="fa-solid fa-sliders me-2"></i> Show Filters
            </button>

            <form id="mnFilters" class="collapse d-lg-block" x-data="shopFilters()">

                <div class="position-sticky" style="top:120px;">

                    <div class="bg-white border rounded-3 p-3 p-md-4 mb-3">
                        <h6 class="text-uppercase fw-bold mb-3" style="font-size:12px; letter-spacing:1.5px; color:#6B7280;">
                            <i class="fa-solid fa-sliders me-2" style="color:#e61f7f;"></i> Filters
                        </h6>

                        {{-- Search --}}
                        <label class="form-label fw-bold" style="font-size:13px; color:#303030;">Search</label>
                        <div class="position-relative mb-3">
                            <input type="search" class="form-control" placeholder="Name or SKU…"
                                   x-model="filters.q"
                                   style="padding-right:36px; border-radius:10px;"
                                   @input.debounce.350ms="apply()">
                            <i class="fa-solid fa-magnifying-glass position-absolute" style="right:14px; top:50%; transform:translateY(-50%); color:#9CA3AF; font-size:13px;"></i>
                        </div>

                        {{-- Availability --}}
                        <div class="form-check mb-1">
                            <input type="checkbox" class="form-check-input" id="f_in_stock" x-model="filters.in_stock" @change="apply()">
                            <label class="form-check-label" for="f_in_stock" style="font-size:14px;">In stock only</label>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="f_on_sale" x-model="filters.on_sale" @change="apply()">
                            <label class="form-check-label" for="f_on_sale" style="font-size:14px;">On sale</label>
                        </div>
                    </div>

                    {{-- Category --}}
                    <div class="bg-white border rounded-3 p-3 p-md-4 mb-3">
                        <h6 class="text-uppercase fw-bold mb-3" style="font-size:12px; letter-spacing:1.5px; color:#6B7280;">Category</h6>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="category" id="cat_all" value=""
                                       x-model="filters.category" @change="apply()">
                                <label class="form-check-label" for="cat_all" style="font-size:14px;">All categories</label>
                            </div>
                            @foreach($categories as $cat)
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="category" id="cat_{{ $cat->id }}" value="{{ $cat->slug }}"
                                           x-model="filters.category" @change="apply()">
                                    <label class="form-check-label" for="cat_{{ $cat->id }}" style="font-size:14px;">
                                        <span class="me-1">{{ $cat->icon ?? '' }}</span> {{ $cat->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Brand --}}
                    @if($brands->isNotEmpty())
                        <div class="bg-white border rounded-3 p-3 p-md-4 mb-3">
                            <h6 class="text-uppercase fw-bold mb-3" style="font-size:12px; letter-spacing:1.5px; color:#6B7280;">Brand</h6>
                            <div class="d-flex flex-column gap-2">
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="brand" id="br_all" value=""
                                           x-model="filters.brand" @change="apply()">
                                    <label class="form-check-label" for="br_all" style="font-size:14px;">All brands</label>
                                </div>
                                @foreach($brands as $b)
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input" name="brand" id="br_{{ $b->id }}" value="{{ $b->slug }}"
                                               x-model="filters.brand" @change="apply()">
                                        <label class="form-check-label" for="br_{{ $b->id }}" style="font-size:14px;">{{ $b->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Price --}}
                    <div class="bg-white border rounded-3 p-3 p-md-4 mb-3">
                        <h6 class="text-uppercase fw-bold mb-3" style="font-size:12px; letter-spacing:1.5px; color:#6B7280;">Price ($)</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" min="0" class="form-control" placeholder="Min"
                                       x-model="filters.minPrice" @input.debounce.450ms="apply()">
                            </div>
                            <div class="col-6">
                                <input type="number" min="0" class="form-control" placeholder="Max"
                                       x-model="filters.maxPrice" @input.debounce.450ms="apply()">
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-pharma-outline w-100" @click="reset()">
                        <i class="fa-solid fa-rotate-left me-1"></i> Clear filters
                    </button>
                </div>
            </form>
        </aside>

        {{-- ═════════ PRODUCT GRID ═════════ --}}
        <main class="col-lg-9" x-data="productGrid()"
              @filters-updated.window="window.currentFilters = $event.detail; page = 1; hasMore = true; load(true)">

            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                <div>
                    <h1 class="mb-1" style="font-weight:800; font-size:clamp(22px, 3vw, 32px); color:#303030;">
                        @if($activeCategory)
                            {{ $activeCategory->name }}
                        @elseif(!empty($initialFilters['search']))
                            Search results for "{{ $initialFilters['search'] }}"
                        @else
                            Shop All Products
                        @endif
                    </h1>
                    <p class="text-muted mb-0" style="font-size:14px;">Genuine medicines, wellness &amp; health products at best prices.</p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <label for="mnSort" class="text-nowrap text-muted" style="font-size:13.5px;">Sort by</label>
                    <select id="mnSort" class="form-select" style="min-width:180px; border-radius:10px;"
                            x-data @change="window.currentFilters.sort = $event.target.value; $dispatch('filters-updated', window.currentFilters)">
                        <option value="newest" {{ ($initialFilters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Newest First</option>
                        <option value="popular" {{ ($initialFilters['sort'] ?? '') === 'popular' ? 'selected' : '' }}>Most Popular</option>
                        <option value="rating" {{ ($initialFilters['sort'] ?? '') === 'rating' ? 'selected' : '' }}>Top Rated</option>
                        <option value="price_asc" {{ ($initialFilters['sort'] ?? '') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_desc" {{ ($initialFilters['sort'] ?? '') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    </select>
                </div>
            </div>

            {{-- Grid (AJAX-populated) --}}
            <div id="product-grid" class="row row-cols-2 row-cols-md-2 row-cols-xl-3 g-3 g-md-4">
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="text-muted mt-2 mb-0" style="font-size:14px;">Loading products…</p>
                </div>
            </div>

            {{-- Load more --}}
            <div class="text-center mt-5" x-show="hasMore">
                <button type="button" class="btn btn-pharma px-5" @click="page++; load()"
                        :disabled="loading" x-bind:class="loading ? 'opacity-75' : ''">
                    <span x-show="!loading">Load more products</span>
                    <span x-show="loading" x-cloak><i class="fa-solid fa-spinner fa-spin me-1"></i> Loading…</span>
                </button>
            </div>
            <div class="text-center text-muted mt-5" x-show="!hasMore && !loading && total > 0" x-cloak style="font-size:14px;">
                <i class="fa-solid fa-check-circle me-1" style="color:#e61f7f;"></i> You've seen everything
            </div>

            {{-- Category description (below products) --}}
            @if($activeCategory && $activeCategory->description)
                <div class="bg-white border rounded-3 p-3 p-md-4 mt-5"
                     style="line-height:1.8; color:#636363; font-size:14.5px;">
                    {!! \App\Support\HtmlSanitizer::clean($activeCategory->description) !!}
                </div>
            @endif
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const INITIAL_FILTERS = @json((object) ($initialFilters ?? []));

    window.currentFilters = {
        q:        INITIAL_FILTERS.q || INITIAL_FILTERS.search || '',
        category: INITIAL_FILTERS.category || '',
        brand:    INITIAL_FILTERS.brand || '',
        minPrice: INITIAL_FILTERS.minPrice || '',
        maxPrice: INITIAL_FILTERS.maxPrice || '',
        sort:     INITIAL_FILTERS.sort || 'newest',
        in_stock: INITIAL_FILTERS.in_stock ? 1 : 0,
        on_sale:  INITIAL_FILTERS.on_sale ? 1 : 0,
    };

</script>
@endpush
