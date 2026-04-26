@extends('layouts.app')

@section('title', 'My Dashboard — MediNova Pharma')

@section('content')

<div class="container">
    <div class="cs_height_45 cs_height_lg_45"></div>
    <ol class="breadcrumb cs_fs_18 mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item active">My Account</li>
    </ol>
    <div class="cs_height_30 cs_height_lg_30"></div>
</div>

<div class="container">
    <div class="cs_account_wrap">

        @include('partials.account-sidebar', ['current' => 'dashboard'])

        <div class="cs_account_content">

            {{-- Personal Profile card --}}
            <div class="cs_account_card cs_radius_10">
                <div class="cs_account_card_head">
                    <h3 class="cs_fs_18 mb-0">Personal Profile</h3>
                    <a class="cs_text_btn cs_accent_color cs_medium" href="{{ route('dashboard') }}">
                        <span>Edit</span>
                    </a>
                </div>
                <div class="cs_account_body">
                    <div class="cs_personal_info">
                        <p>Name: {{ $user->name }}</p>
                        <p>Email: {{ preg_replace('/(?<=.{2}).(?=.*@)/', '*', $user->email) }}</p>
                        <p>Phone: {{ $user->phone ?? 'Not provided' }}</p>
                        @if($user->date_of_birth)
                            <p>Date of Birth: {{ $user->date_of_birth->format('d M Y') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="cs_height_30 cs_height_lg_30"></div>

            {{-- Stats grid --}}
            <div class="row cs_gap_y_20">
                @php
                    $stats = [
                        ['label' => 'Total Orders',    'value' => auth()->user()->orders()->count(),                                                     'color' => 'accent'],
                        ['label' => 'Wishlist Items',  'value' => $wishlistCount,                                                                         'color' => 'accent_strong'],
                        ['label' => 'Wallet Balance',  'value' => '₹'.number_format($wallet?->balance ?? 0, 2),                                           'color' => 'primary'],
                        ['label' => 'Active Orders',   'value' => auth()->user()->orders()->whereNotIn('status', ['delivered','cancelled'])->count(),    'color' => 'accent'],
                    ];
                @endphp
                @foreach($stats as $s)
                    <div class="col-6 col-md-3">
                        <div class="cs_account_card cs_radius_10" style="padding:22px 20px; height:100%;">
                            <p class="mb-1 cs_light" style="font-size:13px;">{{ $s['label'] }}</p>
                            <h3 class="cs_fs_24 cs_semibold mb-0 cs_{{ $s['color'] }}_color">{{ $s['value'] }}</h3>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="cs_height_30 cs_height_lg_30"></div>

            {{-- Recent Orders --}}
            <div class="cs_account_card cs_radius_10">
                <div class="cs_account_card_head cs_type_1">
                    <h3 class="cs_fs_18 mb-0">Recent Orders</h3>
                    <a class="cs_text_btn cs_accent_color cs_medium" href="{{ route('orders.index') }}">
                        <span>View All</span>
                    </a>
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
                                            <td class="{{ $statusColor }}">{{ $order->status->label() }}</td>
                                            <td>₹{{ number_format($order->total, 2) }}</td>
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
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="cs_height_120 cs_height_lg_70"></div>
@endsection
