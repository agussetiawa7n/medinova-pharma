@php
    $phone   = setting('contact.phone', '+91 000000 00000');
    $email   = setting('contact.email', 'support@medinovapharma.com');
    $address = setting('contact.address', 'Jariptaka, Nagpur, Maharashtra');
    $hours   = setting('contact.hours', 'Mon-Sun: 8 AM - 11 PM');
    $fb = setting('social.facebook');
    $tw = setting('social.twitter');
    $ig = setting('social.instagram');
    $li = setting('social.linkedin');
    $about    = setting('footer.about') ?: 'MediNova Pharma is your trusted online pharmacy — 100% genuine medicines, vitamins, and wellness products delivered safely to your doorstep.';
    $shopLinks    = json_decode(setting('footer.shop_links', ''), true) ?: [];
    $supportLinks = json_decode(setting('footer.support_links', ''), true) ?: [];
    $legalLinks   = json_decode(setting('footer.legal_links', ''), true) ?: [];
    $copyright = str_replace('{year}', date('Y'), setting('footer.copyright', '© '.date('Y').' MediNova Pharma. All rights reserved.'));
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

            {{-- Shop Links (dynamic) --}}
            <div class="col-6 col-md-3 col-lg-2">
                <h4>Shop</h4>
                <ul>
                    @foreach($shopLinks as $link)
                        <li><a href="{{ $link['url'] ?? '#' }}">{{ $link['label'] ?? 'Link' }}</a></li>
                    @endforeach
                    @auth<li><a href="{{ route('prescriptions.index') }}">Upload Prescription</a></li>@endauth
                </ul>
            </div>

            {{-- Support Links (dynamic) --}}
            <div class="col-6 col-md-3 col-lg-2">
                <h4>Support</h4>
                <ul>
                    @foreach($supportLinks as $link)
                        <li><a href="{{ $link['url'] ?? '#' }}">{{ $link['label'] ?? 'Link' }}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Legal Links (dynamic) --}}
            <div class="col-6 col-md-3 col-lg-2">
                <h4>Legal</h4>
                <ul>
                    @foreach($legalLinks as $link)
                        <li><a href="{{ $link['url'] ?? '#' }}">{{ $link['label'] ?? 'Link' }}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Newsletter --}}
            <div class="col-12 col-md-9 col-lg-2">
                <h4>Newsletter</h4>
                <p style="font-size:13.5px; margin-bottom:14px;">Get health tips &amp; special offers in your inbox.</p>
                <div x-data="newsletterForm">
                    <div x-show="submitted" x-cloak style="display:none; color:#b81964; font-size:14px; font-weight:600; align-items:center; gap:8px;">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>You're subscribed! Thank you.</span>
                    </div>
                    <form x-show="!submitted" @submit.prevent="subscribe" class="d-flex gap-2">
                        <input type="email" x-model="email" placeholder="your@email.com" required
                               class="form-control flex-grow-1"
                               style="background:#1F2937; border:1px solid #374151; color:#fff; font-size:14px; border-radius:8px;">
                        <button type="submit" :disabled="loading"
                                class="btn btn-pharma" style="white-space:nowrap; padding:8px 18px;">
                            <span x-show="!loading">Subscribe</span>
                            <span x-show="loading">...</span>
                        </button>
                    </form>
                    <p x-show="error" class="mt-2 mb-0" style="color:#FCA5A5; font-size:12px;" x-text="error"></p>
                </div>
            </div>
        </div>

        <div class="mn-footer-bottom d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
            <div>{{ $copyright }}</div>
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
