{{-- Account sidebar — shared by dashboard, orders, order-details, address, account-details, wallet, prescriptions --}}
@php
    $u = auth()->user();
    $current = $current ?? 'dashboard';
    // Generate initials from name: first letter + last word's first letter (or just first 2 letters)
    $lastWord = strrchr($u->name, ' ');
    $initials = strtoupper(substr($u->name, 0, 1) . ($lastWord ? substr($lastWord, 1, 1) : substr($u->name, 1, 1)));
    // Consistent color from name hash
    $hue = crc32($u->name) % 360;
    $avatarBg = "hsl({$hue}, 55%, 45%)";
@endphp

<div class="cs_account_nav cs_radius_10">
    <div class="cs_account_avatar">
        @if($u->avatar)
            <img src="{{ \Illuminate\Support\Str::startsWith($u->avatar, ['http://','https://']) ? $u->avatar : \Illuminate\Support\Facades\Storage::disk('public')->url($u->avatar) }}"
                 alt="{{ $u->name }}" style="width:80px; height:80px; border-radius:50%; object-fit:cover;">
        @else
            <div style="width:80px; height:80px; border-radius:50%; background:{{ $avatarBg }}; color:#fff; display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:700; margin-bottom:8px;">
                {{ $initials }}
            </div>
        @endif
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
