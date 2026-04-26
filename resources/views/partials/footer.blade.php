@php
    $phone   = setting('contact.phone', '+91 98765 43210');
    $email   = setting('contact.email', 'support@medinovapharma.com');
    $address = setting('contact.address', 'A-45, Health Tower, Mumbai 400001');
    $hours   = setting('contact.hours', 'Mon-Sun: 8 AM - 11 PM');
    $fb = setting('social.facebook');
    $tw = setting('social.twitter');
    $ig = setting('social.instagram');
    $li = setting('social.linkedin');
@endphp

<footer class="mn-footer">
    <div class="container-xxl px-3 px-md-4">
        <div class="row g-4 g-lg-5 pb-5">

            {{-- Brand column --}}
            <div class="col-12 col-md-6 col-lg-4">
                <a href="{{ route('home') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none mb-3">
                    <span class="mn-logo-mark"><i class="fa-solid fa-plus" style="font-size:20px; font-weight:900;"></i></span>
                    <span>
                        <span class="d-block" style="font-weight:900; font-size:22px; color:#fff; letter-spacing:-0.4px; line-height:1.1;">Medi<span style="color:#e61f7f;">Nova</span></span>
                        <span class="d-block" style="font-size:10px; color:#6B7280; letter-spacing:2px; font-weight:600; text-transform:uppercase;">Pharma</span>
                    </span>
                </a>
                <p style="font-size:14px; line-height:1.7; max-width:320px;">Your trusted online pharmacy — quality medicines, vitamins and wellness essentials delivered safely to your doorstep across India.</p>

                <ul class="list-unstyled mt-4 mb-0">
                    <li class="d-flex gap-3 mb-2"><i class="fa-solid fa-location-dot mt-1" style="color:#e61f7f;"></i><span style="font-size:14px;">{{ $address }}</span></li>
                    <li class="d-flex gap-3 mb-2"><i class="fa-solid fa-phone mt-1" style="color:#e61f7f;"></i><a href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a></li>
                    <li class="d-flex gap-3 mb-2"><i class="fa-solid fa-envelope mt-1" style="color:#e61f7f;"></i><a href="mailto:{{ $email }}">{{ $email }}</a></li>
                    <li class="d-flex gap-3"><i class="fa-regular fa-clock mt-1" style="color:#e61f7f;"></i><span style="font-size:14px;">{{ $hours }}</span></li>
                </ul>
            </div>

            {{-- Quick Links --}}
            <div class="col-6 col-md-3 col-lg-2">
                <h4>Shop</h4>
                <ul>
                    <li><a href="{{ route('products.index') }}">All Products</a></li>
                    <li><a href="{{ route('products.index', ['sort' => 'newest']) }}">New Arrivals</a></li>
                    <li><a href="{{ route('products.index', ['sort' => 'popular']) }}">Best Sellers</a></li>
                    <li><a href="{{ route('products.index', ['on_sale' => 1]) }}">On Sale</a></li>
                    @auth<li><a href="{{ route('prescriptions.index') }}">Upload Prescription</a></li>@endauth
                </ul>
            </div>

            {{-- Support --}}
            <div class="col-6 col-md-3 col-lg-2">
                <h4>Support</h4>
                <ul>
                    <li><a href="{{ route('contact') }}">Contact Us</a></li>
                    <li><a href="{{ route('page', 'about-us') }}">About Us</a></li>
                    <li><a href="{{ route('page', 'shipping-policy') }}">Shipping</a></li>
                    <li><a href="{{ route('page', 'return-refund-policy') }}">Returns</a></li>
                    @auth<li><a href="{{ route('orders.index') }}">Track Order</a></li>@endauth
                </ul>
            </div>

            {{-- Legal --}}
            <div class="col-6 col-md-3 col-lg-2">
                <h4>Legal</h4>
                <ul>
                    <li><a href="{{ route('page', 'privacy-policy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('page', 'terms-conditions') }}">Terms &amp; Conditions</a></li>
                    <li><a href="{{ route('page', 'shipping-policy') }}">Shipping Policy</a></li>
                    <li><a href="{{ route('page', 'return-refund-policy') }}">Refund Policy</a></li>
                </ul>
            </div>

            {{-- Newsletter --}}
            <div class="col-12 col-md-9 col-lg-2">
                <h4>Newsletter</h4>
                <p style="font-size:13.5px; margin-bottom:14px;">Get health tips &amp; special offers in your inbox.</p>
                <livewire:newsletter-form />
            </div>
        </div>

        <div class="mn-footer-bottom d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
            <div>&copy; {{ date('Y') }} MediNova Pharma. All rights reserved.</div>
            <div class="mn-social d-flex align-items-center">
                @if($fb)<a href="{{ $fb }}" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>@endif
                @if($tw)<a href="{{ $tw }}" target="_blank" rel="noopener" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>@endif
                @if($ig)<a href="{{ $ig }}" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>@endif
                @if($li)<a href="{{ $li }}" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>@endif
            </div>
            <div class="d-flex align-items-center gap-2 opacity-75" style="font-size:22px;">
                <i class="fa-brands fa-cc-visa" title="Visa"></i>
                <i class="fa-brands fa-cc-mastercard" title="Mastercard"></i>
                <i class="fa-brands fa-cc-amex" title="Amex"></i>
                <i class="fa-brands fa-cc-paypal" title="PayPal"></i>
                <span style="font-size:13px; font-weight:700; letter-spacing:0.5px; color:#9CA3AF;">UPI</span>
            </div>
        </div>
    </div>
</footer>
