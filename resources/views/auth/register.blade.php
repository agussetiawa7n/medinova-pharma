@extends('layouts.app')

@section('title', 'Create Account — MediNova Pharma')
@section('meta_description', 'Join MediNova Pharma today — buy genuine medicines and wellness products with fast delivery.')

@section('content')

@include('partials.breadcamp', [
    'bcTitle' => 'Sign-Up for Healthy Living',
    'bcSubtitle' => "Create your account — it's quick and easy.",
    'bcBg' => asset('assets/glowify/images/signup_banner.jpeg'),
])

<div class="cs_height_80 cs_height_lg_80"></div>

<div class="container">
    <div class="cs_signup_card_wrap cs_gray_bg_4 cs_radius_10 cs_bg_filed" data-src="{{ asset('assets/glowify/images/signup_img.jpeg') }}">
        <div class="cs_signup_card">
            <h2 class="cs_fs_36 cs_medium">CREATE ACCOUNT</h2>
            <p class="cs_light mb-0">Enter your details to create your account</p>

            <div class="cs_height_30 cs_height_lg_20"></div>

            @if($errors->any())
                <div class="alert alert-danger" style="border-radius:8px;">
                    @foreach($errors->all() as $err)
                        <div style="font-size:14px;">{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST">
                @csrf

                <label class="cs_semibold">Full Name<span>*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" autofocus required class="cs_form_field">

                <div class="cs_height_20 cs_height_lg_20"></div>

                <label class="cs_semibold">Email Address<span>*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" required class="cs_form_field">

                <div class="cs_height_20 cs_height_lg_20"></div>

                <label class="cs_semibold">Phone Number</label>
                <input type="tel" name="phone" value="{{ old('phone') }}" class="cs_form_field">

                <div class="cs_height_20 cs_height_lg_20"></div>

                <label class="cs_semibold">Create Password<span>*</span></label>
                <div class="cs_password">
                    <input type="password" name="password" required class="cs_password_input cs_form_field">
                    <button class="cs_eye_btn" type="button" onclick="const i=this.previousElementSibling; i.type = i.type==='password'?'text':'password';">
                        <svg width="29" height="16" viewBox="0 0 29 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14.5 0C8.95924 0 3.93459 2.80577 0.22691 7.36308C-0.0756367 7.73644 -0.0756367 8.25807 0.22691 8.63144C3.93459 13.1942 8.95924 16 14.5 16C20.0408 16 25.0654 13.1942 28.7731 8.63692C29.0756 8.26355 29.0756 7.74194 28.7731 7.36857C25.0654 2.80577 20.0408 0 14.5 0ZM14.8975 13.6335C11.2194 13.8476 8.18211 11.0419 8.41347 7.63212C8.6033 4.82086 11.0652 2.54221 14.1025 2.36651C17.7806 2.15237 20.8179 4.95813 20.5865 8.36788C20.3908 11.1736 17.9289 13.4523 14.8975 13.6335ZM14.7136 11.0309C12.7322 11.1462 11.0949 9.63624 11.2254 7.80233C11.3262 6.28689 12.6551 5.06246 14.2924 4.96362C16.2738 4.84832 17.9111 6.35827 17.7806 8.19218C17.6738 9.71311 16.3449 10.9375 14.7136 11.0309Z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>

                <div class="cs_height_20 cs_height_lg_20"></div>

                <label class="cs_semibold">Confirm Password<span>*</span></label>
                <div class="cs_password">
                    <input type="password" name="password_confirmation" required class="cs_password_input cs_form_field">
                    <button class="cs_eye_btn" type="button" onclick="const i=this.previousElementSibling; i.type = i.type==='password'?'text':'password';">
                        <svg width="29" height="16" viewBox="0 0 29 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14.5 0C8.95924 0 3.93459 2.80577 0.22691 7.36308C-0.0756367 7.73644 -0.0756367 8.25807 0.22691 8.63144C3.93459 13.1942 8.95924 16 14.5 16C20.0408 16 25.0654 13.1942 28.7731 8.63692C29.0756 8.26355 29.0756 7.74194 28.7731 7.36857C25.0654 2.80577 20.0408 0 14.5 0ZM14.8975 13.6335C11.2194 13.8476 8.18211 11.0419 8.41347 7.63212C8.6033 4.82086 11.0652 2.54221 14.1025 2.36651C17.7806 2.15237 20.8179 4.95813 20.5865 8.36788C20.3908 11.1736 17.9289 13.4523 14.8975 13.6335ZM14.7136 11.0309C12.7322 11.1462 11.0949 9.63624 11.2254 7.80233C11.3262 6.28689 12.6551 5.06246 14.2924 4.96362C16.2738 4.84832 17.9111 6.35827 17.7806 8.19218C17.6738 9.71311 16.3449 10.9375 14.7136 11.0309Z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>

                <div class="cs_height_30 cs_height_lg_30"></div>

                <div class="cs_custom_checkbox cs_style_1 cs_light">
                    <input type="checkbox" name="terms" id="terms" required value="1" {{ old('terms') ? 'checked' : '' }}>
                    <span>I agree to the <a href="{{ route('page', 'terms-conditions') }}" style="color:inherit; text-decoration:underline;">Terms</a> &amp; <a href="{{ route('page', 'privacy-policy') }}" style="color:inherit; text-decoration:underline;">Privacy Policy</a></span>
                </div>

                <div class="cs_height_30 cs_height_lg_30"></div>

                <button type="submit" class="cs_btn cs_style_1 cs_fs_18 cs_medium w-100">CREATE ACCOUNT</button>

                <div class="cs_height_30 cs_height_lg_30"></div>

                <p class="mb-0 cs_light cs_primary_color">
                    Already have an account? <a href="{{ route('login') }}" class="cs_accent_color cs_semibold">Login Here</a>
                </p>
            </form>
        </div>
    </div>
</div>

<div class="cs_height_150 cs_height_lg_80"></div>
@endsection
