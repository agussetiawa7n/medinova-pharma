@extends('layouts.app')

@section('title', 'Checkout — MediNova Pharma')

@section('content')

@php
    $checkoutBcTitle = \App\Models\Setting::get('checkout.breadcrumb_title', 'Complete Your Order');
    $checkoutBcImg = \App\Models\Setting::get('checkout.breadcrumb_image');
    $checkoutBcBg = $checkoutBcImg ? \Illuminate\Support\Facades\Storage::disk('public')->url($checkoutBcImg) : asset('assets/glowify/images/breadcamp_bg_11.jpg');
@endphp
@include('partials.breadcamp', [
    'bcTitle' => $checkoutBcTitle,
    'bcBg' => $checkoutBcBg,
    'bcCrumbs' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Cart', 'url' => route('cart')],
        ['label' => 'Checkout'],
    ],
])

<div class="cs_height_120 cs_height_lg_70"></div>

<div class="container">

    @if($errors->any())
        <div class="alert alert-danger mb-4" style="border-radius:8px;">
            <ul class="mb-0" style="padding-left:18px;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-warning mb-4">{{ session('error') }}</div>
    @endif

    <form action="{{ route('checkout.store') }}" method="POST"
          x-data="{
              useSaved: {{ $addresses->isNotEmpty() ? 'true' : 'false' }},
              addressId: '{{ old('address_id', $addresses->first()?->id) }}',
              paymentMethod: '{{ old('payment_method', 'cod') }}',
              walletAmount: {{ (float) old('wallet_amount', 0) }},
              walletBalance: {{ (float) ($wallet?->balance ?? 0) }},
              orderTotal: {{ (float) $total }}
          }">
        @csrf

        <div class="row cs_gap_y_40">

            {{-- ═══════════ Billing + Payment column ═══════════ --}}
            <div class="col-lg-7">

                <h2 class="cs_fs_36 cs_secondary_font cs_medium mb-0">BILLING DETAILS</h2>
                <div class="cs_height_16 cs_height_lg_16"></div>
                <p class="cs_light cs_primary_color mb-0">Please enter your billing details:</p>
                <div class="cs_height_35 cs_height_lg_30"></div>

                @if($addresses->isNotEmpty())
                    <div class="mb-4 d-flex flex-wrap gap-4">
                        <div class="cs_custom_checkbox cs_style_1 cs_light">
                            <input type="radio" x-model="useSaved" :value="true" id="addr-saved">
                            <span>Use saved address</span>
                        </div>
                        <div class="cs_custom_checkbox cs_style_1 cs_light">
                            <input type="radio" x-model="useSaved" :value="false" id="addr-new">
                            <span>Add new address</span>
                        </div>
                    </div>

                    <div x-show="useSaved" x-cloak>
                        <div class="row cs_gap_y_20">
                            @foreach($addresses as $address)
                                <div class="col-lg-6">
                                    <label class="d-block cs_radius_8 p-3 cs_light" style="cursor:pointer; border:1.5px solid transparent;"
                                           :style="addressId === '{{ $address->id }}' ? 'border-color:#e61f7f; background:#faeff2;' : 'border-color:rgba(99,99,99,0.25); background:#fff;'">
                                        <div class="d-flex gap-2 align-items-start">
                                            <input type="radio" name="address_id" value="{{ $address->id }}" x-model="addressId" class="mt-1">
                                            <div style="font-size:14px;">
                                                <strong class="cs_primary_color d-block mb-1">{{ trim($address->first_name . ' ' . $address->last_name) }}</strong>
                                                <div>{{ $address->address_line_1 }}@if($address->address_line_2), {{ $address->address_line_2 }}@endif</div>
                                                <div>{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</div>
                                                <div>📞 {{ $address->phone }}</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="cs_height_25 cs_height_lg_25"></div>
                    </div>
                @endif

                <div x-show="!useSaved">
                    <div class="row">
                        <div class="col-lg-12">
                            <label class="cs_semibold">Full Name<span>*</span></label>
                            <input type="text" name="shipping_name" value="{{ old('shipping_name', auth()->user()->name) }}" class="cs_form_field">
                            <div class="cs_height_30 cs_height_lg_20"></div>
                        </div>
                        <div class="col-lg-6">
                            <label class="cs_semibold">Phone<span>*</span></label>
                            <input type="text" name="shipping_phone" value="{{ old('shipping_phone', auth()->user()->phone) }}" class="cs_form_field">
                            <div class="cs_height_30 cs_height_lg_20"></div>
                        </div>
                        <div class="col-lg-6">
                            <label class="cs_semibold">PIN/Postal Code<span>*</span></label>
                            <input type="text" name="shipping_pincode" value="{{ old('shipping_pincode') }}" class="cs_form_field">
                            <div class="cs_height_30 cs_height_lg_20"></div>
                        </div>
                        <div class="col-lg-12">
                            <label class="cs_semibold">Address<span>*</span></label>
                            <textarea name="shipping_address" rows="3" class="cs_form_field">{{ old('shipping_address') }}</textarea>
                            <div class="cs_height_30 cs_height_lg_20"></div>
                        </div>
                        <div class="col-lg-6">
                            <label class="cs_semibold">City<span>*</span></label>
                            <input type="text" name="shipping_city" value="{{ old('shipping_city') }}" class="cs_form_field">
                            <div class="cs_height_30 cs_height_lg_20"></div>
                        </div>
                        <div class="col-lg-6">
                            <label class="cs_semibold">State<span>*</span></label>
                            <input type="text" name="shipping_state" value="{{ old('shipping_state') }}" class="cs_form_field">
                            <div class="cs_height_30 cs_height_lg_20"></div>
                        </div>
                    </div>
                </div>

                <div class="cs_height_30 cs_height_lg_20"></div>

                <label class="cs_semibold">Order Notes (optional)</label>
                <textarea name="notes" rows="3" class="cs_form_field" maxlength="500" placeholder="Any special instructions for delivery…">{{ old('notes') }}</textarea>
            </div>

            {{-- ═══════════ Order Summary column ═══════════ --}}
            <div class="col-xxl-4 col-lg-5 offset-xxl-1">
                <div class="cs_order_card cs_accent_light_bg cs_radius_10">

                    <h3 class="cs_fs_24 cs_medium cs_secondary_font mb-0">ORDER SUMMARY</h3>
                    <div class="cs_height_8 cs_height_lg_8"></div>

                    <ul class="cs_mp_0 cs_order_summary">
                        @foreach($items as $item)
                            <li>
                                <div class="cs_order_summary_list_title">
                                    <h3 class="mb-0 cs_secondary_font cs_semibold cs_fs_16" style="flex:1;">{{ $item->product->name }}</h3>
                                    <h3 class="mb-0 cs_secondary_font cs_semibold cs_fs_16 cs_accent_color">${{ number_format($item->unit_price * $item->quantity, 2) }}</h3>
                                </div>
                                <p>Quantity: <span class="cs_primary_color">{{ $item->quantity }}</span></p>
                                @if($item->variant)
                                    <p>Variant: <span class="cs_primary_color">{{ $item->variant->name }}</span></p>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <div class="cs_height_40 cs_height_lg_30"></div>

                    <ul class="cs_card_price_list cs_type_1 cs_mp_0">
                        <li>
                            <span class="cs_light">Subtotal</span>
                            <span class="cs_semibold cs_primary_color">${{ number_format($subtotal, 2) }}</span>
                        </li>
                        @if(!empty($discount) && $discount > 0)
                            <li>
                                <span class="cs_light">Discount @if(!empty($coupon))({{ $coupon->code }})@endif</span>
                                <span class="cs_semibold cs_accent_color">−${{ number_format($discount, 2) }}</span>
                            </li>
                        @endif
                        <li>
                            <span class="cs_light">Shipping Fee</span>
                            <span class="cs_semibold cs_primary_color">
                                {{ ($shipping ?? 0) > 0 ? '$' . number_format($shipping, 2) : 'FREE' }}
                            </span>
                        </li>
                        @if(!empty($tax) && $tax > 0)
                            <li>
                                <span class="cs_light">{{ \App\Models\Setting::get('pricing.tax_label', 'Tax (18% GST)') }}</span>
                                <span class="cs_semibold cs_primary_color">${{ number_format($tax, 2) }}</span>
                            </li>
                        @endif
                        <li class="cs_total_price">
                            <span class="cs_fs_18 cs_primary_color">Total</span>
                            <span class="cs_fs_18 cs_primary_color">${{ number_format($total, 2) }}</span>
                        </li>
                    </ul>

                    <div class="cs_height_50 cs_height_lg_40"></div>

                    <h3 class="cs_secondary_font cs_fs_24 cs_medium mb-0">PAYMENT METHOD</h3>
                    <div class="cs_height_8 cs_height_lg_8"></div>
                    <p class="mb-0 cs_light">Choose your preferred payment method:</p>
                    <div class="cs_height_20 cs_height_lg_20"></div>

                    <ul class="cs_payment_method_list cs_mp_0">
                        @php
                            $allMethods = [
                                ['value' => 'cod',             'label' => 'Cash on Delivery'],
                                ['value' => 'razorpay',        'label' => 'Razorpay (UPI / Card / Net Banking)'],
                                ['value' => 'stripe',          'label' => 'Stripe (International Card)'],
                                ['value' => 'paypal',          'label' => 'PayPal'],
                                ['value' => 'wallet',          'label' => 'Pay via Wallet (Balance: $' . number_format($wallet?->balance ?? 0, 2) . ')'],
                            ];

                            $methods = array_filter($allMethods, function ($m) use ($enabledMethods) {
                                return in_array($m['value'], $enabledMethods);
                            });
                        @endphp

                        @if(empty($methods))
                            <li>
                                <p class="cs_light mb-0">No payment methods available. Please contact support.</p>
                            </li>
                        @else
                            @foreach($methods as $m)
                                <li>
                                    <div class="cs_custom_checkbox cs_style_1 cs_light">
                                        <input name="payment_method" type="radio" value="{{ $m['value'] }}" x-model="paymentMethod" id="pay_{{ $m['value'] }}">
                                        <span>{{ $m['label'] }}</span>
                                    </div>
                                </li>
                            @endforeach
                        @endif
                    </ul>

                    {{-- Contact for other payment methods --}}
                    <li>
                        <div class="cs_light mt-2">
                            <i class="fa-solid fa-phone me-1" style="color:#e61f7f;"></i>
                            <a href="{{ route('contact') }}" style="color:#e61f7f; text-decoration:underline;">Contact us</a> for other payment methods.
                        </div>
                    </li>

                    <div class="cs_height_40 cs_height_lg_30"></div>

                    <button type="submit" class="cs_btn cs_style_1 cs_fs_18 w-100">
                        <span>PLACE ORDER</span>
                    </button>

                    <p class="text-center cs_light mt-3 mb-0" style="font-size:12.5px;">
                        🔒 Secure checkout. Your data is encrypted end-to-end.
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="cs_height_120 cs_height_lg_70"></div>
@endsection
