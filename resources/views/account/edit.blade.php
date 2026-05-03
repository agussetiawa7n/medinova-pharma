@extends('layouts.app')

@section('title', 'Edit Profile — MediNova Pharma')

@section('content')

<div class="container">
    <div class="cs_height_45 cs_height_lg_45"></div>
    <ol class="breadcrumb cs_fs_18 mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">My Account</a></li>
        <li class="breadcrumb-item active">Edit Profile</li>
    </ol>
    <div class="cs_height_30 cs_height_lg_30"></div>
</div>

<div class="container">
    <div class="cs_account_wrap">
        @include('partials.account-sidebar', ['current' => 'dashboard'])
        <div class="cs_account_content">
            <div class="cs_account_card cs_radius_10">
                <div class="cs_account_card_head">
                    <h3 class="cs_fs_18 mb-0">Edit Profile</h3>
                </div>
                <div class="cs_plr_25" style="padding-top:20px; padding-bottom:30px;">
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-4 text-center">
                            <div class="d-inline-block position-relative">
                                @php $u = auth()->user(); @endphp
                                @if($u->avatar)
                                    <img src="{{ \Illuminate\Support\Str::startsWith($u->avatar, ['http://','https://']) ? $u->avatar : \Illuminate\Support\Facades\Storage::disk('public')->url($u->avatar) }}"
                                         style="width:110px; height:110px; border-radius:50%; object-fit:cover; border:3px solid #e61f7f;">
                                @else
                                    <div style="width:110px; height:110px; border-radius:50%; background:linear-gradient(135deg, #e61f7f, #b81964); color:#fff; display:flex; align-items:center; justify-content:center; font-size:36px; font-weight:800; margin:0 auto;">
                                        {{ strtoupper(substr($u->name, 0, 1).substr(strrchr($u->name, ' ') ?: '', 1, 1) ?: substr($u->name, 0, 2)) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Profile Photo</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                            @error('avatar') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Full Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $u->name) }}" required>
                            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $u->phone) }}">
                                @error('phone') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $u->date_of_birth?->format('Y-m-d')) }}">
                                @error('date_of_birth') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>

                        <button type="submit" class="cs_btn cs_style_1 cs_fs_16 cs_medium">
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="cs_height_120 cs_height_lg_70"></div>
@endsection
