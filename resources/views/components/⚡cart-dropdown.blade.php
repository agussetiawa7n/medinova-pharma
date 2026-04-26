<?php

use Livewire\Component;
use App\Services\CartService;

new class extends Component
{
    public bool $open = false;
    public int $count = 0;
    public array $items = [];
    public string $subtotal = '0.00';

    public function mount(CartService $cart): void
    {
        $this->refresh($cart);
    }

    public function toggle(CartService $cart): void
    {
        $this->open = !$this->open;
        if ($this->open) $this->refresh($cart);
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function refresh(CartService $cart): void
    {
        $data           = $cart->getCartData();
        $this->count    = $data['count'];
        $this->subtotal = number_format($data['subtotal'], 2);
        $this->items    = collect($data['items'])->take(4)->map(fn($i) => [
            'id'        => $i['id'],
            'name'      => $i['name'],
            'qty'       => $i['qty'],
            'price'     => number_format($i['price'], 2),
            'thumbnail' => $i['thumbnail'],
        ])->toArray();
    }

    public function removeItem(int $itemId, CartService $cart): void
    {
        $cart->removeItem($itemId);
        $this->refresh($cart);
        $this->dispatch('cart-updated');
    }
};
?>

<div x-data class="relative">
    <button wire:click="toggle" class="relative p-2 text-gray-600 hover:text-brand-700 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-10H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        @if($count > 0)
        <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] text-[10px] font-bold text-white rounded-full flex items-center justify-center px-1" style="background:#FA4E67">
            {{ $count > 99 ? '99+' : $count }}
        </span>
        @endif
    </button>

    @if($open)
    <div class="absolute right-0 top-full mt-2 w-80 rounded-2xl bg-white shadow-2xl border border-gray-100 z-50"
         wire:click.outside="close" x-transition>
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <span class="font-semibold text-gray-800 text-sm">My Cart ({{ $count }})</span>
            <button wire:click="close" class="text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @if(count($items) === 0)
        <div class="px-4 py-10 text-center text-sm text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-10H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17"/></svg>
            Your cart is empty
        </div>
        @else
        <div class="max-h-64 overflow-y-auto divide-y divide-gray-50">
            @foreach($items as $item)
            <div class="flex items-center gap-3 px-4 py-3">
                @if($item['thumbnail'])
                <img src="{{ $item['thumbnail'] && (str_starts_with($item['thumbnail'], 'http') || str_starts_with($item['thumbnail'], '/')) ? $item['thumbnail'] : asset('images/product-placeholder.svg') }}" class="w-12 h-12 rounded-lg object-cover">
                @else
                <div class="w-12 h-12 rounded-lg bg-brand-50 flex items-center justify-center text-brand-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-800 truncate">{{ $item['name'] }}</p>
                    <p class="text-xs text-gray-500">Qty: {{ $item['qty'] }} &times; ₹{{ $item['price'] }}</p>
                </div>
                <button wire:click="removeItem({{ $item['id'] }})" class="text-gray-300 hover:text-red-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            @endforeach
        </div>
        <div class="px-4 py-3 border-t border-gray-100">
            <div class="flex justify-between items-center mb-3">
                <span class="text-sm text-gray-600">Subtotal</span>
                <span class="font-bold text-gray-900">₹{{ $subtotal }}</span>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('cart') }}" class="flex-1 text-center py-2 rounded-full border border-brand-600 text-brand-700 text-sm font-medium hover:bg-brand-50 transition-colors">View Cart</a>
                <a href="{{ route('checkout') }}" class="flex-1 text-center py-2 rounded-full text-white text-sm font-medium" style="background:linear-gradient(135deg,#FF647B,#FA4E67)">Checkout</a>
            </div>
        </div>
        @endif
    </div>
    @endif
</div>
