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

    {{-- Glowify base stylesheets --}}
    <link rel="stylesheet" href="{{ asset('assets/glowify/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/glowify/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/glowify/css/style.css') }}">

    {{-- Pharma overrides + Vite (Swiper + Bootstrap JS + Alpine + Livewire) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    @stack('head')
</head>
<body>

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

    @livewireScriptConfig
    @stack('scripts')
</body>
</html>
