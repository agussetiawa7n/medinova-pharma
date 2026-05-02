@extends('layouts.app')

@section('title', 'Order Confirmed — MediNova Pharma')

@section('content')

<div class="cs_height_80 cs_height_lg_60"></div>

<div class="container">
    <div class="mx-auto text-center" style="max-width:640px;">

        <div class="mx-auto mb-4" style="width:96px; height:96px; border-radius:50%; background:#faeff2; display:flex; align-items:center; justify-content:center;">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 6L9 17l-5-5" stroke="#e61f7f" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <h1 class="cs_fs_54 cs_semibold mb-2">Order Confirmed!</h1>
        <p class="cs_fs_18 cs_light mb-0">Thank you for your order. We'll send delivery updates to your email and phone.</p>

        <div class="cs_height_30 cs_height_lg_25"></div>

        <div class="cs_order_card cs_accent_light_bg cs_radius_10 text-start">
            <ul class="cs_mp_0 cs_card_price_list">
                <li class="cs_card_price_list_seperator_head cs_primary_color">
                    <span class="cs_medium">Order #</span>
                    <span class="cs_medium">{{ $order->order_number }}</span>
                </li>
                <li>
                    <span class="cs_light">Placed On</span>
                    <span class="cs_light">{{ $order->created_at->format('d M Y, h:i A') }}</span>
                </li>
                <li>
                    <span class="cs_light">Payment Method</span>
                    <span class="cs_light">{{ $order->payment_method?->label() }}</span>
                </li>
                <li>
                    <span class="cs_light">Status</span>
                    <span class="cs_semibold cs_accent_color">{{ $order->status->label() }}</span>
                </li>
                <li class="cs_total_price">
                    <span class="cs_medium cs_fs_24 cs_primary_color">Total</span>
                    <span class="cs_medium cs_fs_24 cs_primary_color">${{ number_format($order->total, 2) }}</span>
                </li>
            </ul>
        </div>

        <div class="cs_height_30 cs_height_lg_25"></div>

        <div class="d-flex flex-wrap gap-3 justify-content-center">
            <a href="{{ route('orders.show', $order) }}" class="cs_btn cs_style_1 cs_fs_16 cs_medium">
                View Order Details
            </a>
            <a href="{{ route('home') }}" class="cs_btn cs_style_1 cs_fs_16 cs_medium cs_type_1">
                Continue Shopping
            </a>
        </div>

        @if($order->payment_method?->value === 'cod')
            <div class="cs_height_30 cs_height_lg_25"></div>
            <p class="cs_light mb-0" style="font-size:13px;">
                💵 Please keep ${{ number_format($order->total, 2) }} ready at delivery.
            </p>
        @endif
    </div>
</div>

<div class="cs_height_120 cs_height_lg_70"></div>
@endsection
