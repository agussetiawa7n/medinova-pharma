{{-- Account sidebar — shared by dashboard, orders, order-details, address, account-details, wallet, prescriptions --}}
@php
    $u = auth()->user();
    $avatar = $u->avatar ? (\Illuminate\Support\Str::startsWith($u->avatar, ['http://','https://']) ? $u->avatar : \Illuminate\Support\Facades\Storage::url($u->avatar)) : asset('assets/glowify/images/avatar_1.jpeg');
    $current = $current ?? 'dashboard';
@endphp

<div class="cs_account_nav cs_radius_10">
    <div class="cs_account_avatar">
        <img src="{{ $avatar }}" alt="{{ $u->name }}">
        <h3 class=""><span>Hello,</span> <br>{{ \Illuminate\Support\Str::words($u->name, 2, '') }}</h3>
    </div>
    <ul class="cs_account_nav_list cs_mp_0">
        <li class="{{ $current === 'dashboard' ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}"><span>Dashboard</span></a>
        </li>
        <li class="{{ $current === 'orders' ? 'active' : '' }}">
            <a href="{{ route('orders.index') }}"><span>Orders</span></a>
        </li>
        <li class="{{ $current === 'wishlist' ? 'active' : '' }}">
            <a href="{{ route('wishlist') }}"><span>Wishlist</span></a>
        </li>
        <li class="{{ $current === 'addresses' ? 'active' : '' }}">
            <a href="{{ route('addresses.index') }}"><span>Addresses</span></a>
        </li>
        <li class="{{ $current === 'wallet' ? 'active' : '' }}">
            <a href="{{ route('wallet') }}"><span>Wallet</span></a>
        </li>
        <li class="{{ $current === 'prescriptions' ? 'active' : '' }}">
            <a href="{{ route('prescriptions.index') }}"><span>Prescriptions</span></a>
        </li>
        <li>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="border-0 bg-transparent p-0 text-start w-100" style="color:inherit;">
                    <span>Logout</span>
                </button>
            </form>
        </li>
    </ul>
</div>
