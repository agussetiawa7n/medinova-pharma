@extends('layouts.app')

@section('title', 'Contact Us — MediNova Pharma')
@section('meta_description', 'Get in touch with MediNova Pharma — customer support, sales inquiries, and 24/7 pharmacist help.')

@section('content')

@php $bcImg = \App\Models\Setting::get('contact.breadcrumb_image'); @endphp
@include('partials.breadcamp', [
    'bcTitle' => setting('contact.breadcrumb_title', 'Contact Us'),
    'bcBg' => $bcImg ? \Illuminate\Support\Facades\Storage::url($bcImg) : asset('assets/glowify/images/breadcamp_bg_7.jpeg'),
    'bcCrumbs' => [['label' => 'Home', 'url' => route('home')], ['label' => 'Contact']],
])

<div class="cs_height_120 cs_height_lg_70"></div>

<div class="container">
    <div class="cs_contact_section cs_gray_bg_4 cs_radius_10">
        <div class="row align-items-center cs_gap_y_40">

            {{-- ── Form column ── --}}
            <div class="col-lg-6">
                <div class="cs_contact_form_wrap">
                    <h2 class="cs_fs_36 cs_medium text-uppercase cs_secondary_font">{{ setting('contact.form_heading', 'GET IN TOUCH') }}</h2>
                    <p class="cs_light">{{ setting('contact.form_subtitle', 'Have a question or need assistance? Fill out the form below and our team will get back to you as soon as possible — usually within 2 hours.') }}</p>

                    @if(session('success'))
                        <div class="alert alert-success" style="border-radius:8px;">{{ session('success') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger" style="border-radius:8px;">
                            @foreach($errors->all() as $err)
                                <div style="font-size:14px;">{{ $err }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('contact.store') }}" method="POST" class="cs_contact_form">
                        @csrf
                        <div class="row">
                            <div class="col-lg-6">
                                <label class="cs_semibold">Name<span>*</span></label>
                                <input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required class="cs_form_field">
                                <div class="cs_height_15 cs_height_lg_15"></div>
                            </div>
                            <div class="col-lg-6">
                                <label class="cs_semibold">Email Address<span>*</span></label>
                                <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required class="cs_form_field">
                                <div class="cs_height_15 cs_height_lg_15"></div>
                            </div>
                            <div class="col-lg-6">
                                <label class="cs_semibold">Phone</label>
                                <input type="tel" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" class="cs_form_field">
                                <div class="cs_height_15 cs_height_lg_15"></div>
                            </div>
                            <div class="col-lg-6">
                                <label class="cs_semibold">Subject<span>*</span></label>
                                <input type="text" name="subject" value="{{ old('subject') }}" required class="cs_form_field">
                                <div class="cs_height_15 cs_height_lg_15"></div>
                            </div>
                            <div class="col-lg-12">
                                <label class="cs_semibold">Message<span>*</span></label>
                                <textarea name="message" cols="30" rows="6" required class="cs_form_field">{{ old('message') }}</textarea>
                                <div class="cs_height_30 cs_height_lg_30"></div>
                            </div>
                            <div class="col-lg-12">
                                <button type="submit" class="cs_btn cs_style_1 cs_fs_18 w-100"><span>SEND MESSAGE</span></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── Contact info column ── --}}
            <div class="col-xl-5 col-lg-6 offset-xl-1">
                <div class="cs_contact_info cs_radius_8 cs_accent_bg">
                    <h2 class="cs_normal cs_fs_36 cs_white_color cs_secondary_font">{{ setting('contact.info_heading', 'CONTACT INFORMATION') }}</h2>
                    <p class="cs_white_color cs_light">{{ setting('contact.info_subtitle', 'For immediate assistance, reach us directly:') }}</p>

                    <div class="cs_contact_info_item">
                        <div class="cs_contact_info_icon">
                            <img src="{{ asset('assets/glowify/images/icons/contact_icon_1.svg') }}" alt="Email">
                        </div>
                        <div class="cs_contact_info_right">
                            <h4 class="cs_fs_16 cs_semibold cs_white_color cs_secondary_font">{{ setting('contact.label_email', 'Customer Support') }}</h4>
                            <a href="mailto:{{ setting('contact.email', 'support@medinovapharma.com') }}" class="cs_light cs_white_color">
                                {{ setting('contact.email', 'support@medinovapharma.com') }}
                            </a>
                        </div>
                    </div>

                    <div class="cs_contact_info_item">
                        <div class="cs_contact_info_icon">
                            <img src="{{ asset('assets/glowify/images/icons/contact_icon_3.svg') }}" alt="Phone">
                        </div>
                        <div class="cs_contact_info_right">
                            <h4 class="cs_fs_16 cs_semibold cs_white_color cs_secondary_font">{{ setting('contact.label_phone', 'Phone (24/7)') }}</h4>
                            <a href="tel:{{ preg_replace('/\s+/', '', setting('contact.phone', '+919876543210')) }}" class="cs_light cs_white_color">
                                {{ setting('contact.phone', '+91 000000 00000') }}
                            </a>
                        </div>
                    </div>

                    <div class="cs_contact_info_item">
                        <div class="cs_contact_info_icon">
                            <img src="{{ asset('assets/glowify/images/icons/contact_icon_2.svg') }}" alt="Address">
                        </div>
                        <div class="cs_contact_info_right">
                            <h4 class="cs_fs_16 cs_semibold cs_white_color cs_secondary_font">{{ setting('contact.label_address', 'Head Office') }}</h4>
                            <span class="cs_light cs_white_color d-block">
                                {{ setting('contact.address', 'Jariptaka, Nagpur, Maharashtra') }}
                            </span>
                            <span class="cs_light cs_white_color" style="font-size:13px;">
                                {{ setting('contact.hours', 'Mon-Sun: 8 AM - 11 PM') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="cs_height_120 cs_height_lg_70"></div>

    {{-- Google Map (placeholder — points to Mumbai default) --}}
    <div class="cs_map cs_radius_10 overflow-hidden">
        <iframe src="{{ setting('contact.map_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d241317.11281381823!2d72.7104273614083!3d19.082502457710602!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be7c6306644edc1%3A0x5da4ed8f8d648c69!2sMumbai%2C%20Maharashtra!5e0!3m2!1sen!2sin!4v1700000000000') }}"
                allowfullscreen loading="lazy" style="width:100%; height:450px; border:0;"></iframe>
    </div>
</div>

<div class="cs_height_120 cs_height_lg_70"></div>
@endsection
