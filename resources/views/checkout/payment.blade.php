@extends('layouts.app')

@section('title', 'Complete Payment — MediNova Pharma')

@section('content')
<div class="min-h-[calc(100vh-200px)] flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-md text-center">

        @if(isset($payload['gateway']) && $payload['gateway'] === 'razorpay')
        {{-- Razorpay JS SDK auto-submit --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-10">
            <div class="w-16 h-16 mx-auto rounded-full bg-brand-50 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-brand-600 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
            </div>
            <p class="text-gray-700 font-medium mb-1">Redirecting to Razorpay…</p>
            <p class="text-sm text-gray-500">Please do not close this window.</p>
        </div>

        @push('scripts')
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var options = {
                key: "{{ $payload['key'] }}",
                amount: "{{ $payload['amount'] }}",
                currency: "{{ $payload['currency'] ?? 'INR' }}",
                name: "MediNova Pharma",
                description: "Order #{{ $order->order_number }}",
                order_id: "{{ $payload['razorpay_order_id'] }}",
                prefill: {
                    name: "{{ $payload['prefill_name'] ?? '' }}",
                    email: "{{ $payload['prefill_email'] ?? '' }}",
                    contact: "{{ $payload['prefill_phone'] ?? '' }}"
                },
                handler: function (response) {
                    fetch("{{ route('checkout.callback', 'razorpay') }}", {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
                        body: JSON.stringify({
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_signature: response.razorpay_signature,
                            order_id: {{ $order->id }}
                        })
                    }).then(r => r.json()).then(d => {
                        if (d.success) window.location.href = d.redirect;
                        else Alpine.store('toast').add(d.message || 'Payment failed', 'error');
                    });
                },
                modal: { ondismiss: function() { window.location.href = "{{ route('checkout') }}"; } }
            };
            var rzp = new Razorpay(options);
            rzp.open();
        });
        </script>
        @endpush

        @elseif(isset($payload['gateway']) && $payload['gateway'] === 'stripe')
        {{-- Stripe Elements --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-8 text-left">
            <h2 class="font-bold text-gray-900 text-xl mb-6 text-center">Card Payment</h2>
            <div id="stripe-errors" class="text-red-500 text-sm mb-4 hidden"></div>
            <div id="card-element" class="border border-gray-200 rounded-xl px-4 py-3 mb-6"></div>
            <button id="stripe-submit" class="btn-primary w-full py-3 font-bold">
                Pay ${{ number_format($order->grand_total, 2) }}
            </button>
        </div>

        @push('scripts')
        <script src="https://js.stripe.com/v3/"></script>
        <script>
        var stripe = Stripe("{{ $payload['publishable_key'] }}");
        var elements = stripe.elements();
        var cardElement = elements.create('card', { style: { base: { fontSize: '16px', color: '#1F2937' } } });
        cardElement.mount('#card-element');

        document.getElementById('stripe-submit').addEventListener('click', function () {
            stripe.confirmCardPayment("{{ $payload['client_secret'] }}", {
                payment_method: { card: cardElement }
            }).then(function (result) {
                if (result.error) {
                    document.getElementById('stripe-errors').textContent = result.error.message;
                    document.getElementById('stripe-errors').classList.remove('hidden');
                } else if (result.paymentIntent.status === 'succeeded') {
                    window.location.href = "{{ route('checkout.callback', 'stripe') }}?payment_intent=" + result.paymentIntent.id;
                }
            });
        });
        </script>
        @endpush

        @else
        <div class="bg-white rounded-2xl border border-gray-100 p-10">
            <p class="text-gray-600">Processing your payment…</p>
        </div>
        @endif

    </div>
</div>
@endsection
