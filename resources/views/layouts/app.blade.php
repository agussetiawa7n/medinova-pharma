<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="no-js">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'MediNova Pharma') — MediNova Pharma</title>
    <meta name="description" content="@yield('meta_description', 'Buy medicines, health products & wellness essentials online at MediNova Pharma. 100% genuine, fast delivery.')">

    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    {{-- DNS prefetch for asset origins — shaves 50-150ms off first request --}}
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    {{-- Fonts with display=swap to prevent FOIT (Flash of Invisible Text) --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Prompt:wght@300;400;500;600;700&display=swap" media="print" onload="this.media='all';this.onload=null">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Prompt:wght@300;400;500;600;700&display=swap"></noscript>

    {{-- Inline font-display override to prevent layout shift from font swap --}}
    <style>@font-face{font-display:swap;}</style>

    {{-- Critical: Bootstrap grid + utilities needed for layout --}}
    <link rel="stylesheet" href="{{ asset('assets/glowify/css/bootstrap.min.css') }}">

    {{-- Deferred: template styles + icons (non-blocking) --}}
    <link rel="stylesheet" href="{{ asset('assets/glowify/css/style.css') }}" media="print" onload="this.media='all';this.onload=null">
    <link rel="stylesheet" href="{{ asset('assets/glowify/css/fontawesome.min.css') }}" media="print" onload="this.media='all';this.onload=null">
    <noscript>
        <link rel="stylesheet" href="{{ asset('assets/glowify/css/style.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/glowify/css/fontawesome.min.css') }}">
    </noscript>

    {{-- Vite: Swiper + Alpine.js + Bootstrap JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body>
    @include('partials.preloader')

    {{-- ═══════════ Toast container (Alpine store) ═══════════ --}}
    <div x-data class="mn-toast-stack">
        <template x-for="t in $store.toast.items" :key="t.id">
            <div class="mn-toast" :class="t.type" x-transition>
                <i class="fa-solid" :class="{
                    'fa-circle-check':t.type==='success',
                    'fa-circle-exclamation':t.type==='error',
                    'fa-triangle-exclamation':t.type==='warning',
                    'fa-circle-info':t.type==='info'
                }"></i>
                <span x-text="t.message"></span>
                <button class="x" @click="$store.toast.remove(t.id)" aria-label="Close">&times;</button>
            </div>
        </template>
    </div>

    {{-- ═══════════ Header (Glowify cs_site_header) ═══════════ --}}
    <header class="cs_site_header cs_style_1 cs_type_1 cs_primary_color cs_sticky_header cs_white_bg">
        @include('partials.top-bar')
        @include('partials.header')
        @include('partials.nav')
        <div class="cs_header_overlay_mobile"></div>
    </header>
    <div class="cs_site_header_height_1"></div>

    {{-- ═══════════ Cart offcanvas (Bootstrap) ═══════════ --}}
    @include('partials.cart-offcanvas')

    {{-- ═══════════ Breadcrumb (optional) ═══════════ --}}
    @hasSection('breadcrumb')
        <div class="mn-breadcrumb">
            <div class="container">
                @yield('breadcrumb')
            </div>
        </div>
    @endif

    {{-- ═══════════ Main content ═══════════ --}}
    <main>
        @yield('content')
    </main>

    {{-- ═══════════ Footer ═══════════ --}}
    @include('partials.footer')

    @stack('scripts')
</body>
</html>
