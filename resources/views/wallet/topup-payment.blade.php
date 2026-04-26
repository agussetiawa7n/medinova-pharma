@extends('layouts.app')

@section('title', 'Add Money — MediNova Pharma')

@section('content')

<div class="cs_height_80 cs_height_lg_60"></div>

<div class="container">
    <div class="mx-auto text-center" style="max-width:520px;">

        <div class="cs_account_card cs_radius_10" style="padding:40px 32px;">
            <div class="mx-auto mb-3" style="width:72px; height:72px; border-radius:50%; background:#faeff2; display:flex; align-items:center; justify-content:center; color:#e61f7f; font-size:32px;">
                <i class="fa-solid fa-wallet"></i>
            </div>

            <h1 class="cs_fs_36 cs_semibold mb-1">Add Money to Wallet</h1>
            <p class="cs_light mb-1" style="font-size:14.5px;">
                Amount: <span class="cs_accent_color cs_semibold">₹{{ number_format($amount, 2) }}</span>
            </p>
            <p class="cs_light mb-0" style="font-size:13px;">
                Redirecting you to Razorpay secure checkout…
            </p>

            <div class="cs_height_30 cs_height_lg_25"></div>

            <button id="rzp-button" type="button" class="cs_btn cs_style_1 cs_fs_16 cs_medium w-100">
                <span>Pay ₹{{ number_format($amount, 2) }} via Razorpay</span>
            </button>

            <a href="{{ route('wallet') }}" class="d-inline-block mt-3 cs_light" style="font-size:13px; text-decoration:none;">
                ← Cancel and go back
            </a>
        </div>
    </div>
</div>

<div class="cs_height_120 cs_height_lg_70"></div>

{{-- Hidden form for Razorpay callback --}}
<form id="rzp-callback-form" action="{{ route('wallet.topup.callback') }}" method="POST" hidden>
    @csrf
    <input type="hidden" name="razorpay_payment_id" id="rzp_payment_id">
    <input type="hidden" name="razorpay_order_id" id="rzp_order_id">
    <input type="hidden" name="razorpay_signature" id="rzp_signature">
</form>

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    (function () {
        var options = {
            key: "{{ $razorpayKey }}",
            amount: {{ (int) ($amount * 100) }},
            currency: "INR",
            name: "MediNova Pharma",
            description: "Wallet Top-up",
            order_id: "{{ $razorpayOrder->id }}",
            prefill: {
                name:    "{{ addslashes($user->name) }}",
                email:   "{{ $user->email }}",
                contact: "{{ $user->phone ?? '' }}"
            },
            theme: { color: "#e61f7f" },
            handler: function (response) {
                document.getElementById('rzp_payment_id').value = response.razorpay_payment_id;
                document.getElementById('rzp_order_id').value   = response.razorpay_order_id;
                document.getElementById('rzp_signature').value  = response.razorpay_signature;
                document.getElementById('rzp-callback-form').submit();
            },
            modal: {
                ondismiss: function () {
                    var btn = document.getElementById('rzp-button');
                    btn.disabled = false;
                    btn.innerHTML = '<span>Pay ₹{{ number_format($amount, 2) }} via Razorpay</span>';
                }
            }
        };

        var rzp = new Razorpay(options);

        document.getElementById('rzp-button').addEventListener('click', function () {
            this.disabled = true;
            this.innerHTML = '<span>Opening Razorpay…</span>';
            rzp.open();
        });

        // Auto-open on page load
        window.addEventListener('load', function () {
            rzp.open();
        });
    })();
</script>
@endpush
@endsection
