@extends('layouts.app')

@section('title', 'My Addresses — MediNova Pharma')

@section('content')

<div class="container">
    <div class="cs_height_45 cs_height_lg_45"></div>
    <ol class="breadcrumb cs_fs_18 mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">My Account</a></li>
        <li class="breadcrumb-item active">Addresses</li>
    </ol>
    <div class="cs_height_30 cs_height_lg_30"></div>
</div>

<div class="container">
    <div class="cs_account_wrap">

        @include('partials.account-sidebar', ['current' => 'addresses'])

        <div class="cs_account_content">

            @if(session('success'))
                <div class="alert alert-success" style="border-radius:8px;">{{ session('success') }}</div>
            @endif

            <div class="cs_account_card cs_radius_10">
                <div class="cs_account_card_head cs_type_1">
                    <h3 class="cs_fs_18 mb-0 cs_semibold">Saved Addresses</h3>
                    <a href="{{ route('addresses.create') }}" class="cs_btn cs_style_1 cs_fs_14 cs_medium" style="padding:8px 16px;">
                        <span>+ Add New</span>
                    </a>
                </div>

                <div class="cs_plr_25" style="padding-top:20px; padding-bottom:24px;">

                    @if($addresses->isEmpty())
                        <div class="text-center py-5">
                            <div style="width:96px; height:96px; border-radius:50%; background:#faeff2; display:flex; align-items:center; justify-content:center; color:#e61f7f; font-size:38px; margin:0 auto 18px;">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <h4 class="cs_fs_24 cs_semibold mb-2">No addresses saved yet</h4>
                            <p class="cs_light mb-4" style="font-size:15px;">Add an address to speed up checkout next time.</p>
                            <a href="{{ route('addresses.create') }}" class="cs_btn cs_style_1 cs_fs_16 cs_medium">
                                <span>Add Your First Address</span>
                            </a>
                        </div>
                    @else
                        <div class="row cs_gap_y_20">
                            @foreach($addresses as $address)
                                <div class="col-md-6">
                                    <div class="cs_address_card cs_radius_5" style="height:100%;">
                                        <h3 class="cs_fs_24 cs_semibold">
                                            {{ $address->label ?? ($address->is_default ? 'Default Address' : 'Address') }}
                                            @if($address->is_default)
                                                <span class="badge ms-2" style="background:#e61f7f; color:#fff; font-size:11px; font-weight:600; padding:3px 8px; border-radius:4px;">Default</span>
                                            @endif
                                        </h3>
                                        <p class="mb-3">
                                            <span class="cs_primary_color">Name:</span> {{ trim($address->first_name . ' ' . $address->last_name) }}<br>
                                            <span class="cs_primary_color">Phone:</span> {{ $address->phone }}<br>
                                            <span class="cs_primary_color">Address:</span> {{ $address->address_line_1 }}@if($address->address_line_2), {{ $address->address_line_2 }}@endif<br>
                                            <span class="cs_primary_color">Town/City:</span> {{ $address->city }}<br>
                                            <span class="cs_primary_color">State:</span> {{ $address->state }}<br>
                                            <span class="cs_primary_color">ZIP Code:</span> {{ $address->postal_code }}<br>
                                            <span class="cs_primary_color">Country:</span> {{ $address->country }}
                                        </p>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <a href="{{ route('addresses.edit', $address) }}" class="cs_btn cs_style_1 cs_fs_14 cs_medium cs_type_1" style="padding:8px 16px;">
                                                <span>Update</span>
                                            </a>
                                            <form action="{{ route('addresses.destroy', $address) }}" method="POST" class="m-0"
                                                  onsubmit="return confirm('Remove this address?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="cs_btn cs_style_1 cs_fs_14 cs_medium" style="padding:8px 16px; background:#6b7280;">
                                                    <span>Remove</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="cs_height_120 cs_height_lg_70"></div>
@endsection
