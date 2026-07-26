@extends('layouts.app')

@section('title', $composition->name . ' — Uses, Side Effects & Precautions')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($composition->meta_description ?: $composition->overview ?: ('Learn about ' . $composition->name . ' — uses, how it works, side effects and safety.')), 155))

@push('head')
@php
    $compCanonical = route('composition.show', $composition->slug);
    $compDesc = \Illuminate\Support\Str::limit(trim(strip_tags($composition->overview ?: ('Uses, mechanism, side effects and precautions of ' . $composition->name . '.'))), 300);

    $drugLd = [
        '@context'    => 'https://schema.org/',
        '@type'       => 'Drug',
        'name'        => $composition->name,
        'activeIngredient' => $composition->name,
        'description' => $compDesc,
        'url'         => $compCanonical,
    ];
    $compBreadcrumb = [
        '@context' => 'https://schema.org/',
        '@type'    => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Shop', 'item' => route('products.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $composition->name, 'item' => $compCanonical],
        ],
    ];
    $compGraph = [$drugLd, $compBreadcrumb];
@endphp
<link rel="canonical" href="{{ $compCanonical }}">
<meta property="og:type" content="article">
<meta property="og:title" content="{{ $composition->name }}">
<meta property="og:description" content="{{ $compDesc }}">
<meta property="og:url" content="{{ $compCanonical }}">
@foreach($compGraph as $ld)
<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endforeach
@endpush

@section('breadcrumb')
    <ol>
        <li><a href="{{ route('home') }}"><i class="fa-solid fa-house me-1"></i> Home</a></li>
        <li class="sep">/</li>
        <li><a href="{{ route('products.index') }}">Shop</a></li>
        <li class="sep">/</li>
        <li aria-current="page">{{ $composition->name }}</li>
    </ol>
@endsection

@section('content')
@php
    $allowedTags = '<h1><h2><h3><h4><h5><h6><p><ul><ol><li><a><strong><b><em><i><br><hr><blockquote><span><div><table><thead><tbody><tr><th><td><sup><sub>';
@endphp
<div class="container py-4">

    {{-- Header --}}
    <div class="mb-4">
        <p class="mb-1" style="color:#e61f7f; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Active Ingredient</p>
        <h1 style="font-size:clamp(24px, 3vw, 34px); font-weight:800; color:#303030;">{{ $composition->name }}</h1>
    </div>

    <div class="row g-4">
        {{-- Main content --}}
        <div class="col-lg-8">
            <div style="line-height:1.8; color:#636363;">
                @if($composition->overview)
                    <h2 class="h4 fw-bold" style="color:#303030;">Overview</h2>
                    {!! \App\Support\HtmlSanitizer::clean($composition->overview) !!}
                @endif

                @if($composition->how_it_works)
                    <h2 class="h4 fw-bold mt-4" style="color:#303030;">How {{ $composition->name }} works</h2>
                    {!! \App\Support\HtmlSanitizer::clean($composition->how_it_works) !!}
                @endif

                @if($composition->uses)
                    <h2 class="h4 fw-bold mt-4" style="color:#303030;">Uses</h2>
                    {!! \App\Support\HtmlSanitizer::clean($composition->uses) !!}
                @endif

                @if($composition->side_effects)
                    <h2 class="h4 fw-bold mt-4" style="color:#303030;">Side effects</h2>
                    {!! \App\Support\HtmlSanitizer::clean($composition->side_effects) !!}
                @endif

                @if($composition->precautions)
                    <h2 class="h4 fw-bold mt-4" style="color:#303030;">Precautions</h2>
                    {!! \App\Support\HtmlSanitizer::clean($composition->precautions) !!}
                @endif

                @if(!$composition->overview && !$composition->how_it_works && !$composition->uses)
                    <p class="text-muted">Detailed information about {{ $composition->name }} is being prepared and will be available soon.</p>
                @endif
            </div>

            {{-- Medical disclaimer (YMYL) --}}
            <div class="medical-disclaimer mt-4" role="note">
                <strong><i class="fa-solid fa-circle-info me-1"></i> Medical Disclaimer:</strong>
                {{ $composition->medical_disclaimer
                    ?: 'The information on this page is for general educational purposes only and is not a substitute for professional medical advice, diagnosis, or treatment. Always consult a qualified doctor or pharmacist before starting, stopping, or changing any medication.' }}
            </div>

            @include('partials._eeat', ['updated' => $composition->content_reviewed_at ?? $composition->updated_at])
        </div>

        {{-- Sidebar: quick facts --}}
        <div class="col-lg-4">
            <div class="p-4 rounded-3" style="background:#F8FAFB; border:1px solid #EDEFF2;">
                <h3 class="h6 fw-bold mb-3" style="color:#303030;">Quick facts</h3>
                <ul class="list-unstyled small mb-0" style="font-size:13.5px; color:#636363;">
                    <li class="mb-2"><strong class="text-dark">Salt / Ingredient:</strong> {{ $composition->name }}</li>
                    <li class="mb-2"><strong class="text-dark">Products available:</strong> {{ $products->count() }}</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Products containing this salt --}}
    <section class="mt-5 pt-4 border-top">
        <div class="mn-section-head mb-3">
            <div>
                <p class="mn-eyebrow">Available medicines</p>
                <h2>Products with {{ $composition->name }}</h2>
            </div>
        </div>

        @if($products->isEmpty())
            <p class="text-muted">No active products with {{ $composition->name }} at the moment.</p>
        @else
            <div class="row g-3 g-md-4">
                @foreach($products as $product)
                    <div class="col-6 col-md-4 col-lg-3">
                        @include('products._card', compact('product'))
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<style>
    .medical-disclaimer {
        padding: 15px 18px; border-left: 4px solid #ef4444;
        background-color: #fef2f2; color: #b91c1c;
        font-size: 13px; line-height: 1.6; border-radius: 6px;
    }
</style>
@endpush
