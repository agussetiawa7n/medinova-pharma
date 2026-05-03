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

    public function edit()
    {
        return view('account.edit');
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'avatar'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($validated);

        return redirect()->route('dashboard')->with('success', 'Profile updated.');
    }
}
