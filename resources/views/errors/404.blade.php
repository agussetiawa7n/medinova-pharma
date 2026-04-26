@extends('layouts.app')

@section('title', '404 — Page Not Found')

@section('content')

<div class="cs_height_100 cs_height_lg_70"></div>

<div class="container">
    <div class="cs_error text-center">
        <img src="{{ asset('assets/glowify/images/404.svg') }}" alt="404 Not Found" style="max-width:420px; width:100%; margin-bottom:24px;">
        <h1 class="cs_fs_54 cs_semibold">Oops! Page Not Found</h1>
        <p class="cs_light mb-4" style="font-size:17px; max-width:480px; margin:0 auto;">
            The page you're looking for doesn't exist or has been moved. Let's get you back on track.
        </p>

        <div class="cs_height_30 cs_height_lg_25"></div>

        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="{{ route('home') }}" class="cs_btn cs_style_1 cs_fs_18 cs_medium">
                <span>Back to Home</span>
            </a>
            <a href="{{ route('products.index') }}" class="cs_btn cs_style_1 cs_fs_18 cs_medium cs_type_1">
                <span>Browse Products</span>
            </a>
        </div>
    </div>
</div>

<div class="cs_height_100 cs_height_lg_70"></div>
@endsection
