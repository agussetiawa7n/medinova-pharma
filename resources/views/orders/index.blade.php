@extends('layouts.app')

@section('title', 'My Orders — MediNova Pharma')

@section('content')

<div class="container">
    <div class="cs_height_45 cs_height_lg_45"></div>
    <ol class="breadcrumb cs_fs_18 mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">My Account</a></li>
        <li class="breadcrumb-item active">Orders</li>
    </ol>
    <div class="cs_height_30 cs_height_lg_30"></div>
</div>

<div class="container">
    <div class="cs_account_wrap">

        @include('partials.account-sidebar', ['current' => 'orders'])

        <div class="cs_account_content">
            <div class="cs_account_card cs_radius_10">
                <div class="cs_account_card_head cs_type_1">
                    <h3 class="cs_fs_18 mb-0">Your Orders</h3>
                    <select onchange="if(this.value){window.location.href='{{ route('orders.index') }}?status='+this.value}else{window.location.href='{{ route('orders.index') }}'}">
                        <option value="" {{ request('status') ? '' : 'selected' }}>All Orders</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>Shipped</option>
                        <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div class="cs_plr_25">
                    @if($orders->isEmpty())
                        <div class="text-center py-5">
                            <p class="text-muted mb-3" style="font-size:15px;">You haven't placed any orders yet.</p>
                            <a href="{{ route('products.index') }}" class="cs_btn cs_style_1 cs_fs_16 cs_medium">Start Shopping</a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="cs_table_1 m-0">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Placed On</th>
                                        <th>Items</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orders as $order)
                                        @php
                                            $statusColor = match($order->status->value) {
                                                'delivered'  => 'cs_primary_color',
                                                'cancelled', 'refunded' => 'cs_ternary_color',
                                                default => 'cs_accent_color',
                                            };
                                        @endphp
                                        <tr>
                                            <td><a href="{{ route('orders.show', $order) }}">#{{ $order->order_number }}</a></td>
                                            <td>{{ $order->created_at->format('d/m/Y') }}</td>
                                            <td>{{ $order->items->count() }} item{{ $order->items->count() === 1 ? '' : 's' }}</td>
                                            <td class="{{ $statusColor }}">{{ $order->status->label() }}</td>
                                            <td>${{ number_format($order->total, 2) }}</td>
                                            <td class="text-end">
                                                <a class="cs_text_btn" href="{{ route('orders.show', $order) }}">
                                                    <span>View Details</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($orders->hasPages())
                            <div class="cs_table_1_footer mt-3">
                                {{ $orders->appends(request()->query())->links() }}
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
