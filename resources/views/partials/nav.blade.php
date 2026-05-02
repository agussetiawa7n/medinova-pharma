@php
    $menuCategories = app('menuCategories');
    $catImage = fn($cat) => $cat->image ? (\Illuminate\Support\Str::startsWith($cat->image, ['http://','https://']) ? $cat->image : \Illuminate\Support\Facades\Storage::disk('public')->url($cat->image)) : asset('assets/glowify/images/nav-category/' . (($cat->sort_order % 9) + 1) . '.jpeg');
    $megaImage = \App\Models\Setting::get('menu.mega_menu_image');
    $megaImageUrl = $megaImage ? \Illuminate\Support\Facades\Storage::disk('public')->url($megaImage) : asset('assets/glowify/images/offer.jpeg');
    $megaTagline = \App\Models\Setting::get('menu.mega_menu_tagline', 'SPECIAL OFFER');
    $megaTitle = \App\Models\Setting::get('menu.mega_menu_title', 'Save 20%');
    $megaBtnText = \App\Models\Setting::get('menu.mega_menu_button_text', 'Shop Now');
@endphp

<div class="cs_bottom_header">
    <div class="container">
        <div class="cs_bottom_header_in">
            <div class="cs_bottom_header_left">
                <div class="cs_nav_wrap">

                    {{-- Mobile branding inside the drawer --}}
                    <div class="cs_site_branding_wrap cs_mobile_show">
                        <a class="cs_site_branding" href="{{ route('home') }}">
                            <img src="{{ asset('assets/glowify/images/logo-medinova.svg') }}" alt="MediNova Pharma">
                        </a>
                        <button class="cs_close_mobile_active" type="button" aria-label="Close menu">
                            <i class="fa-regular fa-circle-xmark"></i>
                        </button>
                    </div>

                    <div class="cs_nav_out">

                        {{-- All Categories mega dropdown --}}
                        <div class="cs_nav_category_wrap cs_dropdown">
                            <span class="cs_nav_category_btn cs_dropdown_btn">All Categories</span>
                            <ul class="cs_nav_category_list cs_dropdown_content">
                                @foreach($menuCategories as $cat)
                                    <li>
                                        <a href="{{ route('products.index', ['category' => $cat->slug]) }}">
                                            <img src="{{ $catImage($cat) }}" alt="{{ $cat->name }}">
                                            <span>{{ $cat->name }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Main nav --}}
                        <nav class="cs_nav cs_mobile_active">
                            <span class="cs_mobile_tab_btn cs_mobile_show">Menu</span>
                            <ul class="cs_nav_list">
                                <li class="{{ request()->routeIs('home') ? 'current-menu-item' : '' }}">
                                    <a href="{{ route('home') }}">Home</a>
                                </li>

                                <li class="menu-item-has-children cs_mega_menu {{ request()->routeIs('products.*') ? 'current-menu-item' : '' }}">
                                    <a href="{{ route('products.index') }}">Shop</a>
                                    <ul class="cs_mega_wrapper">
                                        <li class="menu-item-has-children">
                                            <h4>Categories</h4>
                                            <ul>
                                                @foreach($menuCategories->take(6) as $cat)
                                                    <li><a href="{{ route('products.index', ['category' => $cat->slug]) }}">{{ $cat->name }}</a></li>
                                                @endforeach
                                            </ul>
                                        </li>
                                        <li class="menu-item-has-children">
                                            <h4>Quick Shop</h4>
                                            <ul>
                                                <li><a href="{{ route('products.index') }}">All Products</a></li>
                                                <li><a href="{{ route('products.index', ['sort' => 'newest']) }}">New Arrivals</a></li>
                                                <li><a href="{{ route('products.index', ['sort' => 'popular']) }}">Best Sellers</a></li>
                                                <li><a href="{{ route('products.index', ['on_sale' => 1]) }}">On Sale</a></li>
                                                @auth<li><a href="{{ route('prescriptions.index') }}">Upload Prescription</a></li>@endauth
                                            </ul>
                                        </li>
                                        <li class="menu-item-has-children">
                                            <h4>Your Account</h4>
                                            <ul>
                                                @auth
                                                    <li><a href="{{ route('dashboard') }}">My Account</a></li>
                                                    <li><a href="{{ route('orders.index') }}">My Orders</a></li>
                                                    <li><a href="{{ route('wishlist') }}">Wishlist</a></li>
                                                    <li><a href="{{ route('addresses.index') }}">Addresses</a></li>
                                                    <li><a href="{{ route('wallet') }}">Wallet</a></li>
                                                    <li><a href="{{ route('prescriptions.index') }}">Prescriptions</a></li>
                                                    <li><a href="{{ route('cart') }}">Cart</a></li>
                                                @else
                                                    <li><a href="{{ route('login') }}">Log In</a></li>
                                                    <li><a href="{{ route('register') }}">Sign Up</a></li>
                                                    <li><a href="{{ route('cart') }}">Cart</a></li>
                                                @endauth
                                            </ul>
                                        </li>
                                        <li class="menu-item-has-children">
                                            <a href="{{ route('products.index', ['on_sale' => 1]) }}" class="cs_banner cs_style_5 cs_accent_light_bg cs_radius_10 overflow-hidden position-relative cs_bg_filed" data-src="{{ $megaImageUrl }}">
                                                <div class="cs_banner_text">
                                                    <p class="cs_fs_24 cs_white_color cs_medium">{{ $megaTagline }}</p>
                                                    <h2 class="cs_fs_54 cs_white_color cs_normal cs_secondary_font">{{ $megaTitle }}</h2>
                                                    <span class="cs_banner_lavel cs_accent_strong_bg cs_white_color cs_fs_18 cs_radius_5">{{ $megaBtnText }}</span>
                                                </div>
                                            </a>
                                        </li>
                                    </ul>
                                </li>

                                <li class="menu-item-has-children">
                                    <a href="{{ route('products.index') }}">Categories</a>
                                    <ul>
                                        @foreach($menuCategories->take(8) as $cat)
                                            <li><a href="{{ route('products.index', ['category' => $cat->slug]) }}">{{ $cat->icon ?? '' }} {{ $cat->name }}</a></li>
                                        @endforeach
                                    </ul>
                                </li>

                                @auth
                                    <li><a href="{{ route('prescriptions.index') }}">Upload Rx</a></li>
                                @endauth

                                <li class="menu-item-has-children">
                                    <a href="{{ route('page', 'about-us') }}">Pages</a>
                                    <ul>
                                        <li><a href="{{ route('page', 'about-us') }}">About Us</a></li>
                                        <li><a href="{{ route('contact') }}">Contact</a></li>
                                        <li><a href="{{ route('page', 'shipping-policy') }}">Shipping Policy</a></li>
                                        <li><a href="{{ route('page', 'return-refund-policy') }}">Returns</a></li>
                                        <li><a href="{{ route('page', 'privacy-policy') }}">Privacy Policy</a></li>
                                        <li><a href="{{ route('page', 'terms-conditions') }}">Terms</a></li>
                                    </ul>
                                </li>

                                <li><a href="{{ route('contact') }}">Contact</a></li>
                            </ul>
                        </nav>

                        <div class="cs_header_social cs_mobile_show">
                            @if($fb = setting('social.facebook'))<a href="{{ $fb }}" target="_blank" rel="noopener"><i class="fa-brands fa-facebook-f"></i></a>@endif
                            @if($tw = setting('social.twitter'))<a href="{{ $tw }}" target="_blank" rel="noopener"><i class="fa-brands fa-x-twitter"></i></a>@endif
                            @if($ig = setting('social.instagram'))<a href="{{ $ig }}" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i></a>@endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
