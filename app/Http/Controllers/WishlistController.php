<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user  = Auth::user();
        $items = $user->wishlist()
            ->with('product.brand')
            ->latest()
            ->get();

        return view('wishlist.index', compact('items'));
    }

    public function count()
    {
        $count = 0;
        if (Auth::check()) {
            $count = Wishlist::where('user_id', Auth::id())->count();
        }
        return response()->json(['count' => $count]);
    }

    public function toggle(Request $request)
    {
        $request->validate(['product_id' => 'required|integer|exists:products,id']);

        $existing = Wishlist::where('user_id', Auth::id())
            ->where('product_id', $request->product_id)
            ->first();

        if ($existing) {
            $existing->delete();
            $inWishlist = false;
            $message = 'Removed from wishlist';
        } else {
            Wishlist::create(['user_id' => Auth::id(), 'product_id' => $request->product_id]);
            $inWishlist = true;
            $message = 'Added to wishlist';
        }

        return response()->json([
            'in_wishlist' => $inWishlist,
            'message'     => $message,
        ]);
    }
}
