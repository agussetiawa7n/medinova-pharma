@extends('layouts.app')

@php $isEdit = $address->exists; @endphp

@section('title', ($isEdit ? 'Update Address' : 'Add Address') . ' — MediNova Pharma')

@section('content')

<div class="container">
    <div class="cs_height_45 cs_height_lg_45"></div>
    <ol class="breadcrumb cs_fs_18 mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">My Account</a></li>
        <li class="breadcrumb-item"><a href="{{ route('addresses.index') }}">Addresses</a></li>
        <li class="breadcrumb-item active">{{ $isEdit ? 'Update' : 'Add New' }}</li>
    </ol>
    <div class="cs_height_30 cs_height_lg_30"></div>
</div>

<div class="container">
    <div class="cs_account_wrap">

        @include('partials.account-sidebar', ['current' => 'addresses'])

        <div class="cs_account_content">

            @if($errors->any())
                <div class="alert alert-danger" style="border-radius:8px;">
                    @foreach($errors->all() as $err)
                        <div style="font-size:14px;">{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            <div class="cs_account_card cs_radius_10">
                <div class="cs_account_card_head cs_type_1">
                    <h3 class="cs_fs_18 mb-0">{{ $isEdit ? 'Update Address' : 'Add New Address' }}</h3>
                </div>

                <div class="cs_plr_25" style="padding-top:24px; padding-bottom:24px;">
                    <form action="{{ $isEdit ? route('addresses.update', $address) : route('addresses.store') }}" method="POST">
                        @csrf
                        @if($isEdit) @method('PUT') @endif

                        <hr>
                        <div class="cs_height_25 cs_height_lg_25"></div>

                        <div class="row cs_gap_y_20">

                            <div class="col-lg-12">
                                <label class="cs_medium">Label (optional)</label>
                                <input type="text" name="label" value="{{ old('label', $address->label) }}" placeholder="Home, Office…" class="cs_form_field">
                            </div>

                            <div class="col-lg-6">
                                <label class="cs_medium">First Name <span>*</span></label>
                                <input type="text" name="first_name" value="{{ old('first_name', $address->first_name ?? auth()->user()->name) }}" required class="cs_form_field">
                            </div>

                            <div class="col-lg-6">
                                <label class="cs_medium">Last Name</label>
                                <input type="text" name="last_name" value="{{ old('last_name', $address->last_name) }}" class="cs_form_field">
                            </div>

                            <div class="col-lg-6">
                                <label class="cs_medium">Phone <span>*</span></label>
                                <input type="tel" name="phone" value="{{ old('phone', $address->phone ?? auth()->user()->phone) }}" required class="cs_form_field">
                            </div>

                            <div class="col-lg-6">
                                <label class="cs_medium">Country / Region <span>*</span></label>
                                <input type="text" name="country" value="{{ old('country', $address->country ?? 'India') }}" required class="cs_form_field">
                            </div>

                            <div class="col-lg-12">
                                <label class="cs_medium">Street Address <span>*</span></label>
                                <input type="text" name="address_line_1" value="{{ old('address_line_1', $address->address_line_1) }}" required class="cs_form_field" placeholder="House no., building, street">
                            </div>

                            <div class="col-lg-12">
                                <label class="cs_medium">Apartment / Landmark (optional)</label>
                                <input type="text" name="address_line_2" value="{{ old('address_line_2', $address->address_line_2) }}" class="cs_form_field" placeholder="Apt, suite, landmark…">
                            </div>

                            <div class="col-lg-6">
                                <label class="cs_medium">Town / City <span>*</span></label>
                                <input type="text" name="city" value="{{ old('city', $address->city) }}" required class="cs_form_field">
                            </div>

                            <div class="col-lg-6">
                                <label class="cs_medium">State <span>*</span></label>
                                <input type="text" name="state" value="{{ old('state', $address->state) }}" required class="cs_form_field">
                            </div>

                            <div class="col-lg-6">
                                <label class="cs_medium">ZIP / Postal Code <span>*</span></label>
                                <input type="text" name="postal_code" value="{{ old('postal_code', $address->postal_code) }}" required class="cs_form_field">
                            </div>

                            <div class="col-lg-6 d-flex align-items-end">
                                <div class="cs_custom_checkbox cs_style_1 cs_light">
                                    <input type="checkbox" name="is_default" value="1" id="is_default" {{ old('is_default', $address->is_default) ? 'checked' : '' }}>
                                    <span>Use as default address</span>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="cs_height_10 cs_height_lg_10"></div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="submit" class="cs_btn cs_style_1 cs_fs_16 cs_medium cs_type_1">
                                        <span>{{ $isEdit ? 'Update Address' : 'Save Address' }}</span>
                                    </button>
                                    <a href="{{ route('addresses.index') }}" class="cs_btn cs_style_1 cs_fs_16 cs_medium" style="background:#6b7280;">
                                        <span>Cancel</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="cs_height_120 cs_height_lg_70"></div>
@endsection
