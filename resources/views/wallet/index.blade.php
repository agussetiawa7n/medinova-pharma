@extends('layouts.app')

@section('title', 'My Wallet — MediNova Pharma')

@section('content')

<div class="container">
    <div class="cs_height_45 cs_height_lg_45"></div>
    <ol class="breadcrumb cs_fs_18 mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">My Account</a></li>
        <li class="breadcrumb-item active">Wallet</li>
    </ol>
    <div class="cs_height_30 cs_height_lg_30"></div>
</div>

<div class="container">
    <div class="cs_account_wrap">

        @include('partials.account-sidebar', ['current' => 'wallet'])

        <div class="cs_account_content">

            @if(session('success'))
                <div class="alert alert-success" style="border-radius:8px;">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger" style="border-radius:8px;">{{ session('error') }}</div>
            @endif

            <div class="row cs_gap_y_20">

                {{-- Balance card --}}
                <div class="col-md-6">
                    <div class="cs_account_card cs_radius_10" style="background:linear-gradient(135deg,#e61f7f,#b81964); color:#fff; padding:30px 28px; border:0;">
                        <p class="mb-1" style="font-size:12px; text-transform:uppercase; letter-spacing:2px; opacity:0.8;">Available Balance</p>
                        <h2 style="color:#fff; font-weight:800; font-size:42px; margin:0;">₹{{ number_format($wallet->balance ?? 0, 2) }}</h2>
                        <p class="mb-0 mt-2" style="font-size:13px; opacity:0.85;">
                            Use your wallet at checkout for faster payments.
                        </p>
                    </div>
                </div>

                {{-- Top up card --}}
                <div class="col-md-6">
                    <div class="cs_account_card cs_radius_10" style="padding:26px 28px; height:100%;">
                        <h3 class="cs_fs_18 mb-1">Add Money</h3>
                        <p class="cs_light mb-3" style="font-size:13px;">Top up via Razorpay (UPI, Card, Net Banking).</p>

                        <form action="{{ route('wallet.topup') }}" method="POST">
                            @csrf

                            <div class="d-flex gap-2 flex-wrap mb-3">
                                @foreach([100, 250, 500, 1000, 2000] as $preset)
                                    <button type="button"
                                            onclick="document.getElementById('topup-amount').value = {{ $preset }}"
                                            class="cs_btn cs_style_1 cs_fs_14 cs_type_1" style="padding:6px 14px;">
                                        <span>₹{{ $preset }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <div class="position-relative mb-2">
                                <span class="position-absolute cs_primary_color cs_semibold" style="left:16px; top:50%; transform:translateY(-50%);">₹</span>
                                <input type="number" id="topup-amount" name="amount" min="10" max="50000" step="1"
                                       value="{{ old('amount', 500) }}"
                                       placeholder="Enter amount"
                                       class="cs_form_field" style="padding-left:32px;" required>
                            </div>

                            @error('amount')
                                <p class="cs_ternary_color mb-2" style="font-size:12.5px;">{{ $message }}</p>
                            @enderror

                            <button type="submit" class="cs_btn cs_style_1 cs_fs_16 cs_medium w-100">
                                <span>Add Money to Wallet</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="cs_height_30 cs_height_lg_30"></div>

            {{-- Transactions --}}
            <div class="cs_account_card cs_radius_10">
                <div class="cs_account_card_head cs_type_1">
                    <h3 class="cs_fs_18 mb-0">Transaction History</h3>
                    <span class="cs_light" style="font-size:13px;">{{ $transactions->total() }} total</span>
                </div>

                <div class="cs_plr_25" style="padding-bottom:20px;">
                    @if($transactions->isEmpty())
                        <div class="text-center py-5">
                            <p class="cs_light mb-0" style="font-size:15px;">No transactions yet.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="cs_table_1 m-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Type</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $tx)
                                        @php
                                            $isCredit = $tx->type->value === 'credit';
                                        @endphp
                                        <tr>
                                            <td>{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                            <td>{{ $tx->description ?? '—' }}</td>
                                            <td class="{{ $isCredit ? 'cs_primary_color' : 'cs_ternary_color' }}">
                                                {{ $tx->type->label() }}
                                            </td>
                                            <td class="text-end cs_semibold {{ $isCredit ? 'cs_accent_color' : 'cs_ternary_color' }}" style="font-size:16px;">
                                                {{ $isCredit ? '+' : '−' }}₹{{ number_format($tx->amount, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($transactions->hasPages())
                            <div class="cs_table_1_footer mt-3">
                                {{ $transactions->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="cs_height_120 cs_height_lg_70"></div>
@endsection
