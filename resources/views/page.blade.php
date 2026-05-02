@extends('layouts.app')

@section('title', $page->title . ' — MediNova Pharma')
@section('meta_description', $page->meta_description ?? \Illuminate\Support\Str::limit(strip_tags($page->content ?? ''), 155))

@section('content')

@php
    $bcImg = $page->breadcrumb_image ? \Illuminate\Support\Facades\Storage::url($page->breadcrumb_image) : asset('assets/glowify/images/breadcamp_bg_' . (($page->id ?? 1) % 10 + 1) . '.jpeg');
    $bcTitle = $page->breadcrumb_title ?: $page->title;
@endphp
@include('partials.breadcamp', [
    'bcTitle' => $bcTitle,
    'bcBg' => $bcImg,
    'bcCrumbs' => [['label' => 'Home', 'url' => route('home')], ['label' => $bcTitle]],
])

<div class="cs_height_80 cs_height_lg_60"></div>

<div class="container">
    <div class="row">
        <div class="col-lg-9 mx-auto">
            <article class="cs_account_card cs_radius_10">
                <div class="cs_plr_25" style="padding-top:30px; padding-bottom:30px;">
                    <div class="cs_static_page_content">
                        {!! $page->content !!}
                    </div>

                    <div class="cs_height_40 cs_height_lg_30"></div>

                    <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between pt-3 border-top">
                        <small class="cs_light" style="font-size:13px;">
                            Last updated {{ $page->updated_at->format('d M Y') }}
                        </small>
                        <div class="d-flex gap-2">
                            <a href="{{ route('contact') }}" class="cs_btn cs_style_1 cs_fs_16 cs_medium cs_type_1">Contact Support</a>
                            <a href="{{ route('home') }}" class="cs_btn cs_style_1 cs_fs_16 cs_medium">Back to Home</a>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </div>
</div>

<div class="cs_height_120 cs_height_lg_70"></div>

@push('head')
<style>
    .cs_static_page_content { color:#636363; line-height:1.85; font-size:16px; }
    .cs_static_page_content h1, .cs_static_page_content h2, .cs_static_page_content h3,
    .cs_static_page_content h4, .cs_static_page_content h5, .cs_static_page_content h6 {
        color:#303030; font-family:'Barlow','Plus Jakarta Sans',sans-serif; margin-top:28px; margin-bottom:14px; font-weight:700;
    }
    .cs_static_page_content h2 { font-size:28px; }
    .cs_static_page_content h3 { font-size:22px; }
    .cs_static_page_content h4 { font-size:18px; }
    .cs_static_page_content p  { margin-bottom:18px; }
    .cs_static_page_content ul,
    .cs_static_page_content ol { margin-bottom:18px; padding-left:22px; }
    .cs_static_page_content li { margin-bottom:6px; }
    .cs_static_page_content a  { color:#e61f7f; text-decoration:underline; }
    .cs_static_page_content a:hover { color:#b81964; }
    .cs_static_page_content strong, .cs_static_page_content b { color:#303030; }
    .cs_static_page_content hr { margin:28px 0; }
    .cs_static_page_content blockquote {
        border-left:4px solid #e61f7f; padding:12px 22px; margin:20px 0; background:#faeff2; border-radius:8px; font-style:italic;
    }
</style>
@endpush
@endsection
