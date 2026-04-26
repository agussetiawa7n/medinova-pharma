@php
    $phone = setting('contact.phone', '+91 98765 43210');
    $email = setting('contact.email', 'support@medinovapharma.com');
    $fb    = setting('social.facebook');
    $tw    = setting('social.twitter');
    $ig    = setting('social.instagram');
    $wishlistCount = auth()->check() ? auth()->user()->wishlist()->count() : 0;
@endphp

<div class="cs_top_header cs_primary_bg cs_accent_light_color cs_light">
    <div class="container">
        <div class="cs_top_header_in">
            <div class="cs_top_header_left">
                <ul class="cs_top_header_list cs_mp_0 cs_top_header_info_list">
                    <li>
                        <a href="{{ route('contact') }}" class="cs_header_icon_box">
                            <svg width="15" height="17" viewBox="0 0 15 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M7.5 0.701523C3.36499 0.701523 0 4.17892 0 8.45207C0 9.6973 0.295015 10.9322 0.859985 12.0432L0.024995 15.257C-0.040005 15.5154 0.03 15.7944 0.214995 15.9804C0.350005 16.1251 0.535005 16.2026 0.725005 16.2026C0.790005 16.2026 0.850005 16.1923 0.915005 16.1768L4.02499 15.3139C5.10001 15.8977 6.29501 16.2026 7.5 16.2026C11.635 16.2026 15 12.7252 15 8.45207C15 4.17892 11.635 0.701523 7.5 0.701523ZM7.5 13.1541C7.04501 13.1541 6.67499 12.7717 6.67499 12.3015C6.67499 11.8313 7.04501 11.4489 7.5 11.4489C7.95499 11.4489 8.32501 11.8313 8.32501 12.3015C8.32501 12.7717 7.95499 13.1541 7.5 13.1541ZM8.82501 8.87577C8.45001 9.11343 8.22 9.50613 8.22 9.92983C8.22 10.3432 7.89499 10.6791 7.495 10.6791C7.095 10.6791 6.76999 10.3432 6.76999 9.92983C6.76999 8.97908 7.25501 8.11103 8.07001 7.59432C8.405 7.38249 8.60501 7.01045 8.59501 6.60226C8.59 6.02356 8.10502 5.51203 7.54001 5.4862C6.95002 5.4552 6.45499 5.91504 6.39999 6.51961C6.36499 6.93296 6.005 7.22749 5.60998 7.19649C5.20999 7.16031 4.92002 6.79344 4.95499 6.38009C5.07501 4.98498 6.23001 3.93609 7.60001 3.98775C8.92999 4.04459 10.025 5.20717 10.05 6.58161C10.065 7.51166 9.595 8.39006 8.82501 8.87577Z" fill="currentColor"/>
                            </svg>
                            Help Center
                        </a>
                    </li>
                    <li>
                        <div class="cs_dropdown cs_language_wrap">
                            <button class="cs_language_toggle cs_dropdown_btn" type="button">
                                <img src="{{ asset('assets/glowify/images/icons/language_icon_1.svg') }}" alt="Icon"> Eng
                                <svg width="13" height="7" viewBox="0 0 13 7" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6.50003 7C6.3489 7 6.19763 6.94298 6.08226 6.82908L0.173173 0.995781C-0.0577243 0.767845 -0.0577243 0.398743 0.173173 0.170952C0.40407 -0.0568383 0.777967 -0.0569841 1.00872 0.170952L6.50003 5.59184L11.9913 0.170952C12.2222 -0.0569841 12.5961 -0.0569841 12.8269 0.170952C13.0576 0.398889 13.0578 0.767991 12.8269 0.995781L6.9178 6.82908C6.80242 6.94298 6.65115 7 6.50003 7Z" fill="currentColor"/>
                                </svg>
                            </button>
                            <ul class="cs_dropdown_content cs_language_list">
                                <li><a href="#"><img src="{{ asset('assets/glowify/images/icons/language_icon_1.svg') }}" alt="Icon">Eng - English</a></li>
                                <li><a href="#"><img src="{{ asset('assets/glowify/images/icons/language_icon_2.svg') }}" alt="Icon">हिं - हिंदी</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="cs_top_header_rihgt">
                <ul class="cs_top_header_list cs_mp_0 cs_mobile_hide">
                    @auth
                        <li><a href="{{ route('wishlist') }}">Wish List ({{ $wishlistCount }})</a></li>
                        <li><a href="{{ route('dashboard') }}">My Account</a></li>
                    @else
                        <li><a href="{{ route('login') }}">Log In</a></li>
                        <li><a href="{{ route('register') }}">Register</a></li>
                    @endauth
                    <li>
                        <div class="cs_header_social">
                            @if($fb)<a href="{{ $fb }}" target="_blank" rel="noopener"><i class="fa-brands fa-facebook-f"></i></a>@endif
                            @if($tw)<a href="{{ $tw }}" target="_blank" rel="noopener"><i class="fa-brands fa-x-twitter"></i></a>@endif
                            @if($ig)<a href="{{ $ig }}" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i></a>@endif
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
