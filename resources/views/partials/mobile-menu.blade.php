{{-- Mobile-only offcanvas drawer for navigation --}}
@php
    $menuCats = \App\Models\Category::query()->where('show_in_menu', true)->where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->get();
@endphp

<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="mnMobileMenu" style="max-width:320px; width:86vw;">
    <div class="offcanvas-header border-bottom" style="padding:18px 20px;">
        <a href="{{ route('home') }}" class="d-flex align-items-center gap-2 text-decoration-none">
            <span class="mn-logo-mark"><i class="fa-solid fa-plus" style="font-size:20px; font-weight:900;"></i></span>
            <span>
                <span class="d-block mn-logo-text" style="font-size:18px;">Medi<span class="mn-logo-accent">Nova</span></span>
                <span class="d-block mn-logo-sub">Pharma</span>
            </span>
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="list-unstyled m-0">
            <li><a href="{{ route('home') }}" class="mn-dropdown-item" style="padding:14px 22px;">Home</a></li>
            <li><a href="{{ route('products.index') }}" class="mn-dropdown-item" style="padding:14px 22px;">Shop</a></li>
            <li class="px-4 pt-3 pb-2" style="font-size:11px; color:#9CA3AF; font-weight:700; text-transform:uppercase; letter-spacing:1.2px;">Categories</li>
            @foreach($menuCats as $cat)
                <li>
                    <a href="{{ route('products.index', ['category' => $cat->slug]) }}" class="mn-dropdown-item" style="padding:12px 22px;">
                        <span style="font-size:18px; margin-right:8px;">{{ $cat->icon ?: '💊' }}</span>
                        {{ $cat->name }}
                    </a>
                </li>
            @endforeach
            <li><hr class="my-2"></li>
            @auth
                <li><a href="{{ route('dashboard') }}" class="mn-dropdown-item" style="padding:14px 22px;"><i class="fa-solid fa-gauge me-2" style="color:#e61f7f;"></i> Dashboard</a></li>
                <li><a href="{{ route('orders.index') }}" class="mn-dropdown-item" style="padding:14px 22px;"><i class="fa-solid fa-bag-shopping me-2" style="color:#e61f7f;"></i> My Orders</a></li>
                <li><a href="{{ route('wishlist') }}" class="mn-dropdown-item" style="padding:14px 22px;"><i class="fa-regular fa-heart me-2" style="color:#e61f7f;"></i> Wishlist</a></li>
                <li><a href="{{ route('prescriptions.index') }}" class="mn-dropdown-item" style="padding:14px 22px;"><i class="fa-solid fa-prescription me-2" style="color:#e61f7f;"></i> Prescriptions</a></li>
                <li><a href="{{ route('wallet') }}" class="mn-dropdown-item" style="padding:14px 22px;"><i class="fa-solid fa-wallet me-2" style="color:#e61f7f;"></i> Wallet</a></li>
                <li><hr class="my-2"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="mn-dropdown-item w-100 border-0 bg-transparent text-start" style="padding:14px 22px; color:#DC2626;">
                            <i class="fa-solid fa-right-from-bracket me-2"></i> Sign Out
                        </button>
                    </form>
                </li>
            @else
                <li><a href="{{ route('login') }}" class="mn-dropdown-item" style="padding:14px 22px;"><i class="fa-solid fa-right-to-bracket me-2" style="color:#e61f7f;"></i> Sign In</a></li>
                <li class="px-4 pt-2">
                    <a href="{{ route('register') }}" class="btn btn-pharma w-100">Create Account</a>
                </li>
            @endauth
            <li><hr class="my-2"></li>
            <li><a href="{{ route('page', 'about-us') }}" class="mn-dropdown-item" style="padding:14px 22px;">About Us</a></li>
            <li><a href="{{ route('contact') }}" class="mn-dropdown-item" style="padding:14px 22px;">Contact</a></li>
        </ul>
    </div>
</div>
