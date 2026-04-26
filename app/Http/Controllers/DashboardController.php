<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user   = Auth::user();
        $orders = $user->orders()->with('items.product')->latest()->limit(5)->get();
        $wallet = $user->wallet;
        $wishlistCount = $user->wishlist()->count();

        return view('dashboard.index', compact('user', 'orders', 'wallet', 'wishlistCount'));
    }
}
