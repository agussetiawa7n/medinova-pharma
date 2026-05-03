@extends('layouts.app')

@section('title', 'MediNova Pharma — Your Trusted Online Pharmacy')
@section('meta_description', 'Buy 50,000+ genuine medicines, vitamins, supplements, and health products online with fast delivery and expert pharmacist support.')

@section('content')

{{-- ══════════════════════════════════════════════════ --}}
{{-- HERO (Glowify cs_hero cs_style_2)                   --}}
{{-- ══════════════════════════════════════════════════ --}}
@php
    $heroBg    = \App\Models\Setting::get('home.hero_background') ?: asset('assets/glowify/images/hero_bg_2.jpeg');
    $heroTitle = \App\Models\Setting::get('home.hero_title') ?: 'Your Health, Our Priority';
    $heroSub   = \App\Models\Setting::get('home.hero_subtitle') ?: 'Genuine medicines, vitamins and wellness essentials delivered safely to your doorstep.';
    $heroCta   = \App\Models\Setting::get('home.hero_button_text') ?: 'Shop Now';
    $heroUrl   = \App\Models\Setting::get('home.hero_button_url') ?: route('products.index');
@endphp

@push('head')
<link rel="preload" as="image" href="{{ $heroBg }}" fetchpriority="high">
@endpush

<div class="cs_hero cs_style_2 cs_bg_filed" data-src="{{ $heroBg }}">
    <div class="container">
        <div class="cs_hero_text">
            <h1 class="cs_hero_title cs_fs_100">{{ $heroTitle }}</h1>
            <p class="cs_hero_subtitle cs_fs_24">{{ $heroSub }}</p>
            <a href="{{ $heroUrl }}" class="cs_btn cs_style_1 cs_fs_18 cs_medium">{{ $heroCta }}</a>
        </div>
    </div>
    <div class="cs_star_shape_1 cs_accent_strong_color">
        <svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0.841694 20.8361L7.15422 19.0283C9.99692 18.2251 12.5864 16.7043 14.6748 14.6113C16.7633 12.5183 18.2808 9.92328 19.0823 7.07443L20.8862 0.748229C20.9769 0.526897 21.1312 0.337589 21.3295 0.204361C21.5279 0.0711333 21.7612 0 22 0C22.2388 0 22.4721 0.0711333 22.6705 0.204361C22.8688 0.337589 23.0231 0.526897 23.1138 0.748229L24.9178 7.07443C25.7192 9.92328 27.2367 12.5183 29.3252 14.6113C31.4136 16.7043 34.0031 18.2251 36.8458 19.0283L43.1583 20.8361C43.4007 20.9051 43.614 21.0513 43.7659 21.2528C43.9178 21.4542 44 21.6999 44 21.9524C44 22.2049 43.9178 22.4505 43.7659 22.6519C43.614 22.8534 43.4007 22.9997 43.1583 23.0686L36.8458 24.8764C34.0031 25.6796 31.4136 27.2004 29.3252 29.2934C27.2367 31.3864 25.7192 33.9814 24.9178 36.8303L23.1138 43.1565C23.045 43.3994 22.8991 43.6132 22.698 43.7654C22.497 43.9176 22.252 44 22 44C21.748 44 21.503 43.9176 21.302 43.7654C21.1009 43.6132 20.955 43.3994 20.8862 43.1565L19.0823 36.8303C18.2808 33.9814 16.7633 31.3864 14.6748 29.2934C12.5864 27.2004 9.99692 25.6796 7.15422 24.8764L0.841694 23.0686C0.599314 22.9997 0.385988 22.8534 0.234088 22.6519C0.0821877 22.4505 2.23074e-06 22.2049 2.23074e-06 21.9524C2.23074e-06 21.6999 0.0821877 21.4542 0.234088 21.2528C0.385988 21.0513 0.599314 20.9051 0.841694 20.8361Z" fill="currentColor"/>
        </svg>
    </div>
    <div class="cs_star_shape_2 cs_accent_strong_color">
        <svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0.841694 20.8361L7.15422 19.0283C9.99692 18.2251 12.5864 16.7043 14.6748 14.6113C16.7633 12.5183 18.2808 9.92328 19.0823 7.07443L20.8862 0.748229C20.9769 0.526897 21.1312 0.337589 21.3295 0.204361C21.5279 0.0711333 21.7612 0 22 0C22.2388 0 22.4721 0.0711333 22.6705 0.204361C22.8688 0.337589 23.0231 0.526897 23.1138 0.748229L24.9178 7.07443C25.7192 9.92328 27.2367 12.5183 29.3252 14.6113C31.4136 16.7043 34.0031 18.2251 36.8458 19.0283L43.1583 20.8361C43.4007 20.9051 43.614 21.0513 43.7659 21.2528C43.9178 21.4542 44 21.6999 44 21.9524C44 22.2049 43.9178 22.4505 43.7659 22.6519C43.614 22.8534 43.4007 22.9997 43.1583 23.0686L36.8458 24.8764C34.0031 25.6796 31.4136 27.2004 29.3252 29.2934C27.2367 31.3864 25.7192 33.9814 24.9178 36.8303L23.1138 43.1565C23.045 43.3994 22.8991 43.6132 22.698 43.7654C22.497 43.9176 22.252 44 22 44C21.748 44 21.503 43.9176 21.302 43.7654C21.1009 43.6132 20.955 43.3994 20.8862 43.1565L19.0823 36.8303C18.2808 33.9814 16.7633 31.3864 14.6748 29.2934C12.5864 27.2004 9.99692 25.6796 7.15422 24.8764L0.841694 23.0686C0.599314 22.9997 0.385988 22.8534 0.234088 22.6519C0.0821877 22.4505 2.23074e-06 22.2049 2.23074e-06 21.9524C2.23074e-06 21.6999 0.0821877 21.4542 0.234088 21.2528C0.385988 21.0513 0.599314 20.9051 0.841694 20.8361Z" fill="currentColor"/>
        </svg>
    </div>
</div>

{{-- ══════════════════════════════════════════════════ --}}
{{-- FEATURE STRIP (Glowify cs_grid_5_column)            --}}
{{-- ══════════════════════════════════════════════════ --}}
@if($sections['show_feature_strip'])
<div class="cs_height_80 cs_height_lg_60"></div>
<div class="container">
    <div class="cs_grid_5_column cs_type_1">
        @php $features = [
            ['icon'=>'feature_icon_1.svg', 'title'=>'100% Genuine Medicines'],
            ['icon'=>'feature_icon_2.svg', 'title'=>'Customer Satisfaction'],
            ['icon'=>'feature_icon_3.svg', 'title'=>'Trusted Pharmacy'],
            ['icon'=>'feature_icon_4.svg', 'title'=>'Fast Home Delivery'],
            ['icon'=>'feature_icon_5.svg', 'title'=>'Expert Pharmacist Support'],
        ]; @endphp
        @foreach($features as $f)
            <div class="cs_grid_col">
                <div class="cs_iconbox cs_style_2 cs_radius_6">
                    <img src="{{ asset('assets/glowify/images/icons/' . $f['icon']) }}" alt="Icon">
                    <p class="mb-0 cs_semibold">{{ $f['title'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
<div class="cs_height_80 cs_height_lg_60"></div>
@endif

{{-- ══════════════════════════════════════════════════ --}}
{{-- SHOP BY CATEGORY                                    --}}
{{-- ══════════════════════════════════════════════════ --}}
@if($sections['show_categories'] && $categories->isNotEmpty())
    <section style="padding:64px 0;">
        <div class="container-xxl px-3 px-md-4">
            <div class="mn-section-head" data-aos="fade-up">
                <div>
                    <p class="mn-eyebrow">Explore</p>
                    <h2>Shop by Category</h2>
                </div>
                <a href="{{ route('products.index') }}" class="mn-view-all">View all <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
                @foreach($categories->take(12) as $i => $category)
                    <div class="col" data-aos="fade-up" data-aos-delay="{{ min($i * 40, 300) }}">
                        <a href="{{ route('products.index', ['category' => $category->slug]) }}" class="mn-category-card">
                            <span class="mn-category-icon">{{ $category->icon ?: '💊' }}</span>
                            <p class="mn-category-name">{{ $category->name }}</p>
                            @if(isset($category->products_count))
                                <p class="mn-category-count">{{ $category->products_count }} items</p>
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ══════════════════════════════════════════════════ --}}
{{-- FLASH SALE (Swiper slider)                          --}}
{{-- ══════════════════════════════════════════════════ --}}
@if($sections['show_flash_sale'] && $flashSale->isNotEmpty())
    <section style="padding:32px 0 64px; background:linear-gradient(180deg, #fff 0%, #F8FAFB 100%);">
        <div class="container-xxl px-3 px-md-4">
            <div class="mn-section-head" data-aos="fade-up">
                <div>
                    <p class="mn-eyebrow" style="color:#DC3545;"><i class="fa-solid fa-bolt me-1"></i> Flash Sale</p>
                    <h2>Limited-time Deals</h2>
                </div>
                <a href="{{ route('products.index', ['on_sale' => 1]) }}" class="mn-view-all">View all <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="swiper mn-flash-swiper" data-aos="fade-up">
                <div class="swiper-wrapper">
                    @foreach($flashSale as $product)
                        <div class="swiper-slide" style="height:auto;">
                            @include('products._card', compact('product'))
                        </div>
                    @endforeach
                </div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </div>
    </section>
@endif

{{-- ══════════════════════════════════════════════════ --}}
{{-- PROMO BANNERS (dynamic from admin)                   --}}
{{-- ══════════════════════════════════════════════════ --}}
@php $promoBannerItems = json_decode(\App\Models\Setting::get('home.promo_banners', '[]'), true) ?: []; @endphp
@if($sections['show_promo_banners'] && count($promoBannerItems))
    <section style="padding:8px 0 56px;">
        <div class="container-xxl px-3 px-md-4">
            <div class="row g-3 g-md-4">
                @foreach(array_slice($promoBannerItems, 0, 3) as $banner)
                    <div class="col-12 col-md-{{ count($promoBannerItems) === 1 ? '12' : (count($promoBannerItems) === 2 ? '6' : '4') }}" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">
                        <a href="{{ $banner['button_url'] ?? '#' }}" class="mn-promo text-decoration-none"
                           @if(!empty($banner['image'])) style="background-image:url('{{ \Illuminate\Support\Facades\Storage::url($banner['image']) }}');" @endif>
                            <div class="mn-promo-inner">
                                @if(!empty($banner['badge_text']))
                                    <span class="badge mb-2" style="background:#fff; color:#e61f7f; font-weight:700; font-size:12px;">{{ $banner['badge_text'] }}</span>
                                @endif
                                <h3>{{ $banner['title'] ?? '' }}</h3>
                                @if(!empty($banner['subtitle']))<p>{{ $banner['subtitle'] }}</p>@endif
                                @if(!empty($banner['button_text']))
                                    <span class="btn btn-pharma btn-sm">{{ $banner['button_text'] }} <i class="fa-solid fa-arrow-right ms-1"></i></span>
                                @endif
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ══════════════════════════════════════════════════ --}}
{{-- FEATURED PRODUCTS                                   --}}
{{-- ══════════════════════════════════════════════════ --}}
@if($sections['show_featured'] && $featuredProducts->isNotEmpty())
    <section style="padding:48px 0;">
        <div class="container-xxl px-3 px-md-4">
            <div class="mn-section-head" data-aos="fade-up">
                <div>
                    <p class="mn-eyebrow">Hand-picked</p>
                    <h2>Featured Products</h2>
                </div>
                <a href="{{ route('products.index', ['sort' => 'popular']) }}" class="mn-view-all">View all <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
                @foreach($featuredProducts as $product)
                    <div class="col" data-aos="fade-up" data-aos-delay="{{ min($loop->index * 40, 300) }}">
                        @include('products._card', compact('product'))
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ══════════════════════════════════════════════════ --}}
{{-- RX UPLOAD CTA                                       --}}
{{-- ══════════════════════════════════════════════════ --}}
@if($sections['show_prescription_cta'])
<section style="padding:40px 0;">
    <div class="container-xxl px-3 px-md-4">
        <div class="position-relative overflow-hidden p-4 p-md-5"
             style="border-radius:20px; background:linear-gradient(135deg, #e61f7f 0%, #b81964 100%); color:#fff;" data-aos="fade-up">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <p class="mb-2" style="font-size:13px; letter-spacing:2px; text-transform:uppercase; font-weight:700; opacity:0.85;">Prescription Medicines</p>
                    <h2 class="mb-2" style="color:#fff; font-weight:800; font-size:clamp(22px, 3vw, 34px);">Upload your prescription, we'll do the rest</h2>
                    <p class="mb-0" style="opacity:0.85; font-size:15px; max-width:620px;">Quick verification by certified pharmacists. Get prescription medicines delivered safely to your doorstep.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    @auth
                        <a href="{{ route('prescriptions.index') }}" class="btn btn-light fw-bold" style="color:#e61f7f; padding:14px 30px; border-radius:999px;">
                            <i class="fa-solid fa-upload me-2"></i> Upload Prescription
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-light fw-bold" style="color:#e61f7f; padding:14px 30px; border-radius:999px;">
                            <i class="fa-solid fa-user-plus me-2"></i> Get Started
                        </a>
                    @endauth
                </div>
            </div>
            <div class="position-absolute" style="top:-40px; right:-40px; width:220px; height:220px; border-radius:50%; background:rgba(255,255,255,0.08);"></div>
            <div class="position-absolute" style="bottom:-30px; right:120px; width:120px; height:120px; border-radius:50%; background:rgba(255,255,255,0.06);"></div>
        </div>
    </div>
</section>
@endif

{{-- ══════════════════════════════════════════════════ --}}
{{-- NEW ARRIVALS (Swiper)                               --}}
{{-- ══════════════════════════════════════════════════ --}}
@if($sections['show_new_arrivals'] && $newArrivals->isNotEmpty())
    <section style="padding:48px 0;">
        <div class="container-xxl px-3 px-md-4">
            <div class="mn-section-head" data-aos="fade-up">
                <div>
                    <p class="mn-eyebrow">Fresh stock</p>
                    <h2>New Arrivals</h2>
                </div>
                <a href="{{ route('products.index', ['sort' => 'newest']) }}" class="mn-view-all">View all <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="swiper mn-new-swiper" data-aos="fade-up">
                <div class="swiper-wrapper">
                    @foreach($newArrivals as $product)
                        <div class="swiper-slide" style="height:auto;">
                            @include('products._card', compact('product'))
                        </div>
                    @endforeach
                </div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </div>
    </section>
@endif

{{-- ══════════════════════════════════════════════════ --}}
{{-- PROMO GRID (dynamic 2-column, from admin)            --}}
{{-- ══════════════════════════════════════════════════ --}}
@php
    $promoLeft  = json_decode(\App\Models\Setting::get('home.promo_left_banner', '{}'), true) ?: [];
    $promoRight = json_decode(\App\Models\Setting::get('home.promo_right_banner', '{}'), true) ?: [];
    $hasPromoGrid = !empty($promoLeft['title']) || !empty($promoRight['title']);
@endphp
@if($hasPromoGrid)
<section data-aos="fade-up">
    <div class="container">
        <div class="row cs_gap_y_20">
            @if(!empty($promoLeft['title']))
            <div class="{{ !empty($promoRight['title']) ? 'col-lg-6' : 'col-12' }}">
                <a href="{{ $promoLeft['url'] ?? '#' }}"
                   class="cs_banner cs_style_4 cs_accent_light_bg cs_radius_10 overflow-hidden position-relative cs_bg_filed"
                   @if(!empty($promoLeft['image'])) style="background-image:url('{{ \Illuminate\Support\Facades\Storage::url($promoLeft['image']) }}');" @else data-src="{{ asset('assets/glowify/images/banner/banner_img_4.jpeg') }}" @endif>
                    <div class="cs_banner_text">
                        <p class="cs_fs_24 cs_white_color cs_medium">{{ $promoLeft['title'] }}</p>
                        @if(!empty($promoLeft['subtitle']))<h2 class="cs_fs_54 cs_white_color mb-0 cs_normal cs_secondary_font">{{ $promoLeft['subtitle'] }}</h2>@endif
                    </div>
                </a>
            </div>
            @endif
            @if(!empty($promoRight['title']))
            <div class="{{ !empty($promoLeft['title']) ? 'col-lg-6' : 'col-12' }}">
                <a href="{{ $promoRight['url'] ?? '#' }}"
                   class="cs_banner cs_style_5 cs_accent_light_bg cs_radius_10 overflow-hidden position-relative cs_bg_filed"
                   @if(!empty($promoRight['image'])) style="background-image:url('{{ \Illuminate\Support\Facades\Storage::url($promoRight['image']) }}');" @else data-src="{{ asset('assets/glowify/images/banner/banner_img_5.jpeg') }}" @endif>
                    <div class="cs_banner_text">
                        <p class="cs_fs_24 cs_white_color cs_medium">{{ $promoRight['title'] }}</p>
                        @if(!empty($promoRight['subtitle']))<h2 class="cs_fs_54 cs_white_color cs_normal cs_secondary_font">{{ $promoRight['subtitle'] }}</h2>@endif
                    </div>
                </a>
            </div>
            @endif
        </div>
    </div>
</section>
@endif

{{-- ══════════════════════════════════════════════════ --}}
{{-- BEST SELLERS                                        --}}
{{-- ══════════════════════════════════════════════════ --}}
@if($sections['show_best_sellers'] && $bestSellers->isNotEmpty())
    <section style="padding:48px 0 64px;">
        <div class="container-xxl px-3 px-md-4">
            <div class="mn-section-head" data-aos="fade-up">
                <div>
                    <p class="mn-eyebrow">Customer favourites</p>
                    <h2>Best Sellers</h2>
                </div>
                <a href="{{ route('products.index', ['sort' => 'popular']) }}" class="mn-view-all">View all <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
                @foreach($bestSellers as $product)
                    <div class="col" data-aos="fade-up" data-aos-delay="{{ min($loop->index * 40, 300) }}">
                        @include('products._card', compact('product'))
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ══════════════════════════════════════════════════ --}}
@if($sections['show_faq'])
{{-- FAQ SECTION (Glowify cs_accordians cs_style_1)       --}}
{{-- ══════════════════════════════════════════════════ --}}
<section class="cs_accent_light_bg">
    <div class="cs_height_140 cs_height_lg_70"></div>
    <div class="container">
        <div class="cs_section_heading cs_style_1 justify-content-center" data-aos="fade-up">
            <div class="cs_section_heading_in">
                <h3 class="cs_section_heading_title cs_fs_54 cs_semibold mb-0 d-flex align-items-center cs_accent_color">
                    Got Questions? We've Got Answers!
                </h3>
            </div>
        </div>
        <div class="cs_height_60 cs_height_lg_50"></div>
        <div class="row">
            <div class="col-lg-8 offset-lg-2" data-aos="fade-up">
                <div class="cs_accordians cs_style_1 cs_light cs_type_1">

                    @php
                        $faqs = json_decode(\App\Models\Setting::get('home.faqs', ''), true) ?: [
                            ['question' => 'Are all medicines on MediNova Pharma 100% genuine?', 'answer' => 'Yes — every product is sourced directly from licensed manufacturers and authorised distributors. We operate as a registered pharmacy and provide an authenticity guarantee on every order, with batch-level traceability.'],
                            ['question' => 'How do I order prescription medicines?', 'answer' => 'Simply upload a clear photo or PDF of your prescription at checkout or from your dashboard. Our licensed pharmacists verify it within 2–4 hours during business hours, and dispatch your order as soon as it is approved.'],
                            ['question' => 'What is the delivery timeline?', 'answer' => 'Metro cities: 1–2 business days. Tier 2 cities: 2–4 business days. Express 4-hour delivery is available in select areas for an additional fee. You\'ll receive SMS & email tracking updates once shipped.'],
                            ['question' => 'What is your return policy?', 'answer' => 'Sealed, unopened OTC products can be returned within 7 days of delivery. Prescription medicines, opened products, cold-chain items, and personal-care products are non-returnable for safety and hygiene reasons.'],
                            ['question' => 'Do you accept cash on delivery?', 'answer' => 'Yes — COD is available on orders up to $5,000 in most serviceable pincodes. We also accept UPI, credit/debit cards, net banking, and MediNova Wallet. All transactions are secured with 256-bit SSL encryption.'],
                            ['question' => 'Are your products cruelty-free and safe?', 'answer' => 'All medicines are manufactured per the Indian Pharmacopoeia and stored under controlled temperature. Personal-care and wellness brands on our platform explicitly state their cruelty-free/vegan certifications on the product page.'],
                        ];
                    @endphp

                    @foreach($faqs as $i => $faq)
                        <div class="cs_accordian {{ $i === 0 ? 'active' : '' }}">
                            <div class="cs_accordian_head">
                                <h3 class="cs_accordian_title cs_primary_color cs_fs_24 fw-medium mb-0">{{ $faq['question'] }}</h3>
                                <span class="cs_accordian_toggle"></span>
                            </div>
                            <div class="cs_accordian_body">
                                <p class="cs_secondary_color cs_font_26 fw-light mb-0">{{ $faq['answer'] }}</p>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>
        </div>
    </div>
    <div class="cs_height_110 cs_height_lg_30"></div>
</section>
@endif

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const swiperOpts = {
            slidesPerView: 2,
            spaceBetween: 16,
            navigation: { prevEl: '.swiper-button-prev', nextEl: '.swiper-button-next' },
            breakpoints: {
                576: { slidesPerView: 2, spaceBetween: 16 },
                768: { slidesPerView: 3, spaceBetween: 20 },
                1200: { slidesPerView: 4, spaceBetween: 24 },
            },
        };

        document.querySelectorAll('.mn-flash-swiper, .mn-new-swiper').forEach(el => {
            new Swiper(el, swiperOpts);
        });

        // Swiper adds slides/arrows to the DOM — give the browser a tick, then
        // let AOS recompute offsets so later sections aren't stuck hidden on
        // fast scroll.
        if (window.AOS) {
            requestAnimationFrame(() => window.AOS.refreshHard());
            setTimeout(() => window.AOS.refreshHard(), 600);
        }
    });
</script>
<style>
    /* Swiper nav buttons (pharma style) */
    .mn-flash-swiper, .mn-new-swiper { padding: 8px 8px 40px; margin: 0 -8px; }
    .mn-flash-swiper .swiper-button-prev,
    .mn-flash-swiper .swiper-button-next,
    .mn-new-swiper .swiper-button-prev,
    .mn-new-swiper .swiper-button-next {
        width: 40px; height: 40px; background:#fff; border-radius:50%;
        box-shadow: 0 6px 16px rgba(15,23,42,0.10); color:#e61f7f;
        top: calc(50% - 24px);
    }
    .mn-flash-swiper .swiper-button-prev::after,
    .mn-flash-swiper .swiper-button-next::after,
    .mn-new-swiper .swiper-button-prev::after,
    .mn-new-swiper .swiper-button-next::after { font-size: 16px; font-weight: 800; }
    .mn-flash-swiper .swiper-button-prev.swiper-button-disabled,
    .mn-flash-swiper .swiper-button-next.swiper-button-disabled,
    .mn-new-swiper .swiper-button-prev.swiper-button-disabled,
    .mn-new-swiper .swiper-button-next.swiper-button-disabled { opacity: 0; pointer-events:none; }
</style>
@endpush
