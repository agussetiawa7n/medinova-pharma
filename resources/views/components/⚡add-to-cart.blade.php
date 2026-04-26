<?php

use Livewire\Component;
use App\Services\CartService;

new class extends Component
{
    public int $productId;
    public ?int $variantId = null;
    public int $quantity = 1;
    public int $maxQty = 99;

    public function mount(int $productId, ?int $variantId = null, int $stock = 99): void
    {
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->maxQty    = max(1, $stock);
    }

    public function increment(): void
    {
        if ($this->quantity < $this->maxQty) $this->quantity++;
    }

    public function decrement(): void
    {
        if ($this->quantity > 1) $this->quantity--;
    }

    public function addToCart(CartService $cart): void
    {
        $cart->addItem($this->productId, $this->quantity, $this->variantId);
        $this->dispatch('cart-updated');
        $this->dispatch('toast', type: 'success', message: 'Added to cart!');
    }
};
?>

<div class="flex items-center gap-3">
    <div class="flex items-center border border-gray-200 rounded-full overflow-hidden">
        <button wire:click="decrement" class="px-3 py-2 text-gray-500 hover:bg-gray-50 transition-colors text-lg font-light">−</button>
        <span class="px-4 py-2 text-sm font-semibold text-gray-800 min-w-[2.5rem] text-center">{{ $quantity }}</span>
        <button wire:click="increment" class="px-3 py-2 text-gray-500 hover:bg-gray-50 transition-colors text-lg font-light">+</button>
    </div>
    <button wire:click="addToCart"
            @class(['btn-primary flex items-center gap-2 flex-1 justify-center', 'opacity-75 cursor-not-allowed' => $maxQty === 0])
            @disabled($maxQty === 0)>
        @if($maxQty === 0)
            Out of Stock
        @else
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-10H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Add to Cart
        @endif
    </button>
</div>
