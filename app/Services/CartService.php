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
    public function __construct(
        private readonly PricingService $pricing,
    ) {}

    public function getCart(): Cart
    {
        if (Auth::check()) {
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

            // Merge guest cart on login
            $sessionId = Session::get('cart_session_id');
            if ($sessionId) {
                $guestCart = Cart::where('session_id', $sessionId)->first();
                if ($guestCart) {
                    // Pass cart to avoid N getCart() calls inside the loop
                    foreach ($guestCart->items as $item) {
                        $this->addToCart($item->product_id, $item->quantity, $item->product_variant_id, $cart);
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

    public function addToCart(int $productId, int $quantity = 1, ?int $variantId = null, ?Cart $cart = null): CartItem
    {
        $cart  ??= $this->getCart();
        $product = Product::findOrFail($productId);
        $variant = $variantId ? ProductVariant::findOrFail($variantId) : null;

        $price = $variant ? $variant->price : $product->price;

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

    public function addItem(int $productId, int $quantity = 1, ?int $variantId = null): array
    {
        $cart = $this->getCart();
        $this->addToCart($productId, $quantity, $variantId, $cart);

        // Refresh the items relation to get accurate count including the new item
        return [
            'count'   => (int) $cart->items()->sum('quantity'),
            'message' => 'Added to cart',
        ];
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
        $item = $cart->items()->find($itemId);
        if ($item) {
            $item->delete();
        }
    }

    public function clearCart(): void
    {
        $this->getCart()->items()->delete();
    }

    public function getCartCount(): int
    {
        // Lightweight: find existing cart, don't create one just to count
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->first();
        } else {
            $sid = Session::get('cart_session_id');
            $cart = $sid ? Cart::where('session_id', $sid)->first() : null;
        }

        if (!$cart) return 0;

        return (int) $cart->items()->sum('quantity');
    }

    public function getSubtotal(): float
    {
        // Lightweight: find the cart ID without eager-loading all relations
        if (Auth::check()) {
            $cartId = Cart::where('user_id', Auth::id())->value('id');
        } else {
            $sid = Session::get('cart_session_id');
            $cartId = $sid ? Cart::where('session_id', $sid)->value('id') : null;
        }

        if (!$cartId) return 0.0;

        return (float) CartItem::where('cart_id', $cartId)
            ->sum(\Illuminate\Support\Facades\DB::raw('unit_price * quantity'));
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
                'message' => 'Minimum order amount is $' . $coupon->min_order_amount . ' for this coupon.',
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
        $cart  = $this->getCart(); // getCart() already eager-loads items.product + items.variant
        $items = $cart->items;

        $pricing = $this->pricing->calculate($cart);

        return [
            'items'       => $items,
            'count'       => $items->sum('quantity'),
            'subtotal'    => $pricing['subtotal'],
            'discount'    => $pricing['discount'],
            'coupon'      => $pricing['coupon'],
            'coupon_code' => $cart->coupon_code,
            'shipping'    => $pricing['shipping'],
            'tax'         => $pricing['tax'],
            'total'       => $pricing['total'],
            'grand_total' => $pricing['grandTotal'],
        ];
    }
}
