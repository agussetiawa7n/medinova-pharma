<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Razorpay\Api\Api as RazorpayApi;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    public function index()
    {
        /** @var \App\Models\User $user */
        $user         = Auth::user();
        $wallet       = $user->wallet ?? $this->walletService->getOrCreate($user->id);
        $transactions = $wallet->transactions()->latest()->paginate(15);

        return view('wallet.index', compact('wallet', 'transactions'));
    }

    public function topup(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10|max:50000',
        ]);

        $amount = (float) $request->amount;
        $user   = Auth::user();

        $key    = config('services.razorpay.key');
        $secret = config('services.razorpay.secret');

        if (empty($key) || empty($secret)) {
            return back()->with('error', 'Online payment is not configured. Please contact support.');
        }

        $api = new RazorpayApi($key, $secret);

        $order = $api->order->create([
            'amount'   => (int) ($amount * 100),
            'currency' => 'INR',
            'receipt'  => 'wallet-topup-' . $user->id . '-' . time(),
            'notes'    => ['user_id' => $user->id, 'purpose' => 'wallet_topup'],
        ]);

        return view('wallet.topup-payment', [
            'razorpayOrder' => $order,
            'amount'        => $amount,
            'razorpayKey'   => $key,
            'user'          => $user,
        ]);
    }

    public function topupCallback(Request $request)
    {
        $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id'   => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        $key    = config('services.razorpay.key');
        $secret = config('services.razorpay.secret');

        $api = new RazorpayApi($key, $secret);

        try {
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature'  => $request->razorpay_signature,
            ]);
        } catch (\Exception) {
            return redirect()->route('wallet')->with('error', 'Payment verification failed. Please contact support.');
        }

        // Fetch the order to get the amount
        $rzpOrder = $api->order->fetch($request->razorpay_order_id);
        $amount   = $rzpOrder->amount / 100;

        $user = Auth::user();
        $this->walletService->credit(
            $user->id,
            $amount,
            "Wallet top-up via Razorpay (#{$request->razorpay_payment_id})",
            'razorpay',
            null
        );

        return redirect()->route('wallet')->with('success', '₹' . number_format($amount, 2) . ' added to your wallet successfully!');
    }
}

