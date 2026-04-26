<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index()
    {
        $data = $this->cart->getCartData();
        return view('cart.index', $data);
    }

    public function count()
    {
        return response()->json(['count' => $this->cart->getCartCount()]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity'   => 'integer|min:1|max:100',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
        ]);

        $this->cart->addItem(
            $request->product_id,
            $request->quantity ?? 1,
            $request->variant_id
        );

        return response()->json([
            'count'   => $this->cart->getCartCount(),
            'message' => 'Added to cart',
        ]);
    }

    public function update(Request $request, int $id)
    {
        $request->validate(['quantity' => 'required|integer|min:1|max:100']);
        $this->cart->updateItem($id, $request->quantity);

        return response()->json(['message' => 'Cart updated']);
    }

    public function remove(int $id)
    {
        $this->cart->removeItem($id);
        return response()->json(['message' => 'Item removed']);
    }

    public function applyCoupon(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $result = $this->cart->applyCoupon($request->code);

        if ($result['success']) {
            return back()->with('success', 'Coupon applied!');
        }
        return back()->withErrors(['code' => $result['message']]);
    }

    public function removeCoupon()
    {
        $this->cart->removeCoupon();
        return back()->with('success', 'Coupon removed.');
    }
}
