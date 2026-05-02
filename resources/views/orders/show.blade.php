@extends('layouts.app')

@section('title', 'Order #' . $order->order_number . ' — MediNova Pharma')

@section('content')

@php
    $statusColor = match($order->status->value) {
        'delivered'  => 'cs_primary_color',
        'cancelled', 'refunded' => 'cs_ternary_color',
        default => 'cs_accent_color',
    };
@endphp

<div class="container">
    <div class="cs_height_45 cs_height_lg_45"></div>
    <ol class="breadcrumb cs_fs_18 mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">My Account</a></li>
        <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">Orders</a></li>
        <li class="breadcrumb-item active">#{{ $order->order_number }}</li>
    </ol>
    <div class="cs_height_30 cs_height_lg_30"></div>
</div>

<div class="container">
    <div class="cs_account_wrap">

        @include('partials.account-sidebar', ['current' => 'orders'])

        <div class="cs_account_content">

            {{-- Main order card --}}
            <div class="cs_account_card cs_radius_10">
                <div class="cs_account_card_head cs_type_1">
                    <h3 class="cs_fs_18 mb-0">Order ID: #{{ $order->order_number }}</h3>
                    <span class="{{ $statusColor }} cs_semibold">{{ $order->status->label() }}</span>
                </div>

                <div class="cs_plr_25">
                    <ul class="cs_card_price_list cs_mp_0">
                        <li class="cs_card_price_list_seperator_head cs_primary_color">
                            <span class="cs_medium">Items</span>
                            <span class="cs_medium">Price</span>
                        </li>

                        @foreach($order->items as $item)
                            <li>
                                <span class="cs_light">
                                    {{ $item->product->name ?? $item->product_name }}
                                    <small style="color:#9CA3AF;">× {{ $item->quantity }}</small>
                                    @if($item->variant_name)
                                        <small style="display:block; color:#9CA3AF;">{{ $item->variant_name }}</small>
                                    @endif
                                </span>
                                <span class="cs_light">${{ number_format($item->total, 2) }}</span>
                            </li>
                        @endforeach

                        <li class="cs_card_price_list_seperator_up">
                            <span class="cs_light">Subtotal</span>
                            <span class="cs_semibold cs_primary_color">${{ number_format($order->subtotal, 2) }}</span>
                        </li>
                        @if($order->discount_amount > 0)
                            <li>
                                <span class="cs_light">Discount</span>
                                <span class="cs_semibold cs_accent_color">−${{ number_format($order->discount_amount, 2) }}</span>
                            </li>
                        @endif
                        @if($order->shipping_amount > 0)
                            <li>
                                <span class="cs_light">Shipping Fee</span>
                                <span class="cs_semibold cs_primary_color">${{ number_format($order->shipping_amount, 2) }}</span>
                            </li>
                        @endif
                        @if($order->tax_amount > 0)
                            <li>
                                <span class="cs_light">Tax (18% GST)</span>
                                <span class="cs_semibold cs_primary_color">${{ number_format($order->tax_amount, 2) }}</span>
                            </li>
                        @endif
                        @if($order->wallet_amount_used > 0)
                            <li>
                                <span class="cs_light">Wallet Used</span>
                                <span class="cs_semibold cs_accent_color">−${{ number_format($order->wallet_amount_used, 2) }}</span>
                            </li>
                        @endif

                        <li class="cs_total_price">
                            <span class="cs_medium cs_fs_24 cs_primary_color">Total</span>
                            <span class="cs_medium cs_fs_24 cs_primary_color">${{ number_format($order->total, 2) }}</span>
                        </li>
                    </ul>

                    <div class="cs_delivery_address">
                        <div>
                            <b>Delivery Address:</b>
                            Name: {{ $order->shipping_name }} <br>
                            Number: {{ $order->shipping_phone }} <br>
                            Address: {{ $order->shipping_address_line_1 }}@if($order->shipping_address_line_2), {{ $order->shipping_address_line_2 }}@endif,
                            {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}, {{ $order->shipping_country }}
                        </div>
                        <div class="text-end">
                            <b>Paid by</b>
                            {{ $order->payment_method?->label() }}
                            <br>
                            <small class="cs_light">{{ $order->payment_status?->value }}</small>
                        </div>
                    </div>

                    @if($order->tracking_number)
                        <div class="cs_delivery_address" style="margin-top:14px;">
                            <div>
                                <b>Tracking Number:</b>
                                <span class="cs_accent_color cs_medium">{{ $order->tracking_number }}</span>
                                @if($order->tracking_url)
                                    <a href="{{ $order->tracking_url }}" target="_blank" class="cs_accent_color ms-2">Track →</a>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($order->notes)
                        <div class="cs_delivery_address" style="margin-top:14px;">
                            <div>
                                <b>Order Notes:</b><br>
                                {{ $order->notes }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if($order->statusHistories->isNotEmpty())
                <div class="cs_height_30 cs_height_lg_30"></div>
                <div class="cs_account_card cs_radius_10">
                    <div class="cs_account_card_head">
                        <h3 class="cs_fs_18 mb-0">Order Timeline</h3>
                    </div>
                    <div class="cs_plr_25" style="padding-bottom:20px;">
                        <ul class="cs_mp_0" style="list-style:none;">
                            @foreach($order->statusHistories as $history)
                                <li style="padding:12px 0; border-bottom:1px solid #eee; display:flex; justify-content:space-between; gap:16px;">
                                    <div>
                                        <p class="mb-1 cs_semibold cs_primary_color">{{ $history->status }}</p>
                                        @if($history->comment)
                                            <p class="mb-0 cs_light" style="font-size:14px;">{{ $history->comment }}</p>
                                        @endif
                                    </div>
                                    <small class="cs_light">{{ $history->created_at->format('d M Y, h:i A') }}</small>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="cs_height_120 cs_height_lg_70"></div>
@endsection
