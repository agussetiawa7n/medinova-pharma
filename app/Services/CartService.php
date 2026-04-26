<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartService
{
    public function getCart(): Cart
    {
        if (Auth::check()) {
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

            // Merge guest cart on login
            $sessionId = Session::get('cart_session_id');
            if ($sessionId) {
                $guestCart = Cart::where('session_id', $sessionId)->first();
                if ($guestCart) {
                    foreach ($guestCart->items as $item) {
                        $this->addToCart($item->product_id, $item->quantity, $item->product_variant_id);
                    }
                    $guestCart->delete();
                }
                Session::forget('cart_session_id');
            }
        } else {
            if (!Session::has('cart_session_id')) {
                Session::put('cart_session_id', Session::getId());
            }
            $cart = Cart::firstOrCreate(['session_id' => Session::get('cart_session_id')]);
        }

        return $cart->load('items.product', 'items.variant');
    }

    public function addToCart(int $productId, int $quantity = 1, ?int $variantId = null): CartItem
    {
        $cart    = $this->getCart();
        $product = Product::findOrFail($productId);
        $variant = $variantId ? ProductVariant::findOrFail($variantId) : null;

        $price   = $variant ? $variant->price : $product->price;

        $item = $cart->items()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($item) {
            $item->increment('quantity', $quantity);
        } else {
            $item = $cart->items()->create([
                'product_id'         => $productId,
                'product_variant_id' => $variantId,
                'quantity'           => $quantity,
                'unit_price'         => $price,
            ]);
        }

        return $item;
    }

    public function addItem(int $productId, int $quantity = 1, ?int $variantId = null): CartItem
    {
        return $this->addToCart($productId, $quantity, $variantId);
    }

    public function updateItem(int $itemId, int $quantity): void
    {
        $cart = $this->getCart();
        $item = $cart->items()->findOrFail($itemId);

        if ($quantity <= 0) {
            $item->delete();
        } else {
            $item->update(['quantity' => $quantity]);
        }
    }

    public function removeItem(int $itemId): void
    {
        $cart = $this->getCart();
        $cart->items()->findOrFail($itemId)->delete();
    }

    public function clearCart(): void
    {
        $this->getCart()->items()->delete();
    }

    public function getItemCount(): int
    {
        return $this->getCart()->items->sum('quantity');
    }

    public function getCartCount(): int
    {
        return $this->getItemCount();
    }

    public function getSubtotal(): float
    {
        return $this->getCart()->subtotal;
    }

    public function applyCoupon(string $code): array
    {
        $coupon = \App\Models\Coupon::where('code', strtoupper($code))->first();

        if (!$coupon || !$coupon->isValid()) {
            return ['success' => false, 'message' => 'Invalid or expired coupon code.'];
        }

        $subtotal = $this->getSubtotal();

        if ($subtotal < $coupon->min_order_amount) {
            return [
                'success' => false,
                'message' => "Minimum order amount is ₹{$coupon->min_order_amount} for this coupon.",
            ];
        }

        $cart = $this->getCart();
        $cart->update(['coupon_code' => $coupon->code]);

        return [
            'success'  => true,
            'discount' => $coupon->calculateDiscount($subtotal),
            'coupon'   => $coupon,
        ];
    }

    public function removeCoupon(): void
    {
        $this->getCart()->update(['coupon_code' => null]);
    }

    public function getCartData(): array
    {
        $cart  = $this->getCart();
        $cart->load(['items.product', 'items.variant']);
        $items = $cart->items;

        $subtotal = $items->sum(fn ($i) => round($i->unit_price * $i->quantity, 2));

        $coupon   = null;
        $discount = 0;
        if ($cart->coupon_code) {
            $coupon = \App\Models\Coupon::where('code', $cart->coupon_code)->first();
            if ($coupon) {
                $discount = $coupon->calculateDiscount($subtotal);
            }
        }

        $freeShippingAt = (int) (\App\Models\Setting::get('site.free_shipping_threshold') ?? 499);
        $flatShipping   = (int) (\App\Models\Setting::get('site.delivery_fee') ?? 50);
        $shipping       = $subtotal > 0 && $subtotal < $freeShippingAt ? $flatShipping : 0;
        $tax            = round(($subtotal - $discount) * 0.18, 2);
        $total          = round($subtotal - $discount + $shipping + $tax, 2);

        return [
            'items'       => $items,
            'count'       => $items->sum('quantity'),
            'subtotal'    => round($subtotal, 2),
            'discount'    => round($discount, 2),
            'coupon'      => $coupon,
            'coupon_code' => $cart->coupon_code,
            'shipping'    => $shipping,
            'tax'         => $tax,
            'total'       => $total,
            'grand_total' => $total,
        ];
    }
}
