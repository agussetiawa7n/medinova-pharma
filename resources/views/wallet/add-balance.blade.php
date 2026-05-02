@extends('layouts.app')

@section('title', 'Add Wallet Balance — MediNova Pharma')

@section('content')

<div class="container">
    <div class="cs_height_45 cs_height_lg_45"></div>
    <ol class="breadcrumb cs_fs_18 mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">My Account</a></li>
        <li class="breadcrumb-item"><a href="{{ route('wallet') }}">Wallet</a></li>
        <li class="breadcrumb-item active">Add Balance</li>
    </ol>
    <div class="cs_height_30 cs_height_lg_30"></div>
</div>

<div class="container">
    <div class="mx-auto" style="max-width:720px;">

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="alert alert-success" style="border-radius:8px; display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-circle-check cs_fs_20"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger" style="border-radius:8px; display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-circle-exclamation cs_fs_20"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Step 1: Guidelines --}}
        <div class="cs_account_card cs_radius_10" style="padding:32px 28px; margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
                <div style="width:40px; height:40px; border-radius:50%; background:#e61f7f; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:18px; flex-shrink:0;">1</div>
                <h3 class="cs_fs_20 cs_semibold mb-0">How to Purchase Wallet Balance</h3>
            </div>

            <div style="padding-left:52px;">
                <p class="cs_light mb-3" style="font-size:14px; line-height:1.7;">
                    Follow the simple steps below to add balance to your wallet. Please make sure to read each step carefully.
                </p>

                <div style="background:#f8fafc; border-radius:10px; padding:20px; border-left:3px solid #e61f7f;">
                    <ol style="margin:0; padding-left:18px; list-style-type:decimal;">
                        <li style="margin-bottom:12px; font-size:14px; line-height:1.6;">
                            <strong>Visit our payment site</strong><br>
                            Click the button below to go to our secure payment portal:
                            <a href="{{ $purchaseUrl ?: '#' }}" target="_blank" rel="noopener" style="color:#e61f7f; font-weight:600; word-break:break-all;">
                                {{ $purchaseUrl ?: 'Payment site URL not configured' }}
                            </a>
                        </li>
                        <li style="margin-bottom:12px; font-size:14px; line-height:1.6;">
                            <strong>Select "Wallet Top-up" option</strong><br>
                            On the payment site, choose the wallet top-up or balance recharge option.
                        </li>
                        <li style="margin-bottom:12px; font-size:14px; line-height:1.6;">
                            <strong>Enter amount and complete payment</strong><br>
                            Choose your desired amount and complete the payment using any available method (UPI, Card, Net Banking, etc.).
                        </li>
                        <li style="margin-bottom:12px; font-size:14px; line-height:1.6;">
                            <strong>Receive unique redemption code</strong><br>
                            After successful payment, you will receive a unique code on screen and via email. Save this code — you'll need it in the next step.
                        </li>
                        <li style="font-size:14px; line-height:1.6;">
                            <strong>Come back and paste the code below</strong><br>
                            Return to this page and paste your code in the "Redeem Your Code" section. Your wallet balance will be updated instantly.
                        </li>
                    </ol>
                </div>
            </div>
        </div>

        {{-- Step 2: Tutorial Video --}}
        @if($tutorialVideo)
        <div class="cs_account_card cs_radius_10" style="padding:32px 28px; margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
                <div style="width:40px; height:40px; border-radius:50%; background:#e61f7f; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:18px; flex-shrink:0;">2</div>
                <h3 class="cs_fs_20 cs_semibold mb-0">Watch Tutorial Video</h3>
            </div>

            <div style="padding-left:52px;">
                <p class="cs_light mb-3" style="font-size:14px;">
                    Watch this step-by-step video guide to understand the complete process.
                </p>

                @php
                    $embedUrl = '';
                    $videoUrl = $tutorialVideo;
                    if (str_contains($videoUrl, 'youtube.com/watch?v=')) {
                        parse_str(parse_url($videoUrl, PHP_URL_QUERY) ?? '', $params);
                        $videoId = $params['v'] ?? '';
                        $embedUrl = $videoId ? "https://www.youtube.com/embed/{$videoId}" : '';
                    } elseif (str_contains($videoUrl, 'youtu.be/')) {
                        $videoId = trim(parse_url($videoUrl, PHP_URL_PATH) ?? '', '/');
                        $embedUrl = $videoId ? "https://www.youtube.com/embed/{$videoId}" : '';
                    } elseif (str_contains($videoUrl, 'vimeo.com/')) {
                        $videoId = trim(parse_url($videoUrl, PHP_URL_PATH) ?? '', '/');
                        $embedUrl = $videoId ? "https://player.vimeo.com/video/{$videoId}" : '';
                    }
                @endphp

                @if($embedUrl)
                <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:10px; background:#000;">
                    <iframe
                        src="{{ $embedUrl }}"
                        style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                        title="Wallet Top-up Tutorial">
                    </iframe>
                </div>
                @else
                <div style="background:#f8fafc; border-radius:10px; padding:24px; text-align:center;">
                    <p class="cs_light mb-0" style="font-size:14px;">
                        <a href="{{ $tutorialVideo }}" target="_blank" rel="noopener" style="color:#e61f7f; font-weight:600;">
                            Click here to watch the tutorial video →
                        </a>
                    </p>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Step 3: Redeem Code --}}
        <div class="cs_account_card cs_radius_10" style="padding:32px 28px; margin-bottom:20px; border:2px solid #e61f7f;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
                <div style="width:40px; height:40px; border-radius:50%; background:#e61f7f; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:18px; flex-shrink:0;">
                    {{ $tutorialVideo ? '3' : '2' }}
                </div>
                <h3 class="cs_fs_20 cs_semibold mb-0">Redeem Your Code</h3>
            </div>

            <div style="padding-left:52px;">
                <p class="cs_light mb-3" style="font-size:14px;">
                    After completing payment on our external site, paste the unique code you received below.
                </p>

                <form action="{{ route('wallet.redeem.code') }}" method="POST">
                    @csrf
                    <div class="position-relative mb-3">
                        <textarea
                            name="code"
                            rows="3"
                            placeholder="Paste your unique redemption code here..."
                            class="cs_form_field"
                            style="width:100%; resize:vertical; font-family:monospace; font-size:13px;"
                            required
                        >{{ old('code') }}</textarea>
                    </div>

                    @error('code')
                        <p class="cs_ternary_color mb-2" style="font-size:12.5px;">{{ $message }}</p>
                    @enderror

                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <button type="submit" class="cs_btn cs_style_1 cs_fs_16 cs_medium">
                            <span><i class="fa-solid fa-check-circle"></i> Redeem & Add Balance</span>
                        </button>
                        <a href="{{ $purchaseUrl ?: '#' }}" target="_blank" rel="noopener" class="cs_btn cs_style_1 cs_fs_16" style="background:#475569;">
                            <span><i class="fa-solid fa-external-link-alt"></i> Go to Payment Site</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Troubleshooting --}}
        <div class="cs_account_card cs_radius_10" style="padding:24px 28px; margin-bottom:20px; background:#fffbe6;">
            <div style="display:flex; align-items:flex-start; gap:10px;">
                <i class="fa-solid fa-circle-info cs_fs_18" style="color:#d97706; margin-top:2px;"></i>
                <div>
                    <h4 class="cs_fs_16 cs_semibold mb-1" style="color:#92400e;">Need Help?</h4>
                    <ul style="margin:0; padding-left:16px; font-size:13px; color:#92400e; line-height:1.8;">
                        <li><strong>Code not working?</strong> Make sure you've copied the entire code exactly as shown. Check for extra spaces.</li>
                        <li><strong>Code already used?</strong> Each code can only be redeemed once. Contact support if you believe this is an error.</li>
                        <li><strong>Payment deducted but no code?</strong> Wait a few minutes and check your email. If still missing, contact our support team with your payment reference.</li>
                        <li><strong>Code expired?</strong> Redemption codes are valid for 7 days from the date of purchase.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Back link --}}
        <div class="text-center mb-5">
            <a href="{{ route('wallet') }}" class="cs_light" style="font-size:14px; text-decoration:none;">
                <i class="fa-solid fa-arrow-left"></i> Back to Wallet
            </a>
        </div>
    </div>
</div>

<div class="cs_height_120 cs_height_lg_70"></div>
@endsection
