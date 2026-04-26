<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Component;

class CartDropdown extends Component
{
    public int $count = 0;
    public string $subtotal = '0.00';
    public array $items = [];

    protected CartService $cartService;

    public function boot(CartService $cartService): void
    {
        $this->cartService = $cartService;
    }

    public function mount(): void
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        $data = $this->cartService->getCartData();
        $this->count = $this->cartService->getCartCount();
        $this->items = $data['items']
            ->map(fn ($item) => [
                'id'       => $item->id,
                'name'     => $item->product?->name ?? 'Product',
                'price'    => (float) $item->unit_price,
                'quantity' => $item->quantity,
                'image'    => $item->product?->thumbnail_url,
                'slug'     => $item->product?->slug,
                'line'     => (float) $item->unit_price * $item->quantity,
            ])->toArray();
        $this->subtotal = number_format(array_sum(array_column($this->items, 'line')), 2);
    }

    public function removeItem(int $itemId): void
    {
        $this->cartService->removeItem($itemId);
        $this->refresh();
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        return view('livewire.cart-dropdown');
    }
}
