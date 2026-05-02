<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function addBalance()
    {
        $externalEnabled = $this->walletService->isExternalTopupEnabled();
        $purchaseUrl     = $this->walletService->getPurchaseUrl();
        $tutorialVideo   = $this->walletService->getTutorialVideoUrl();

        return view('wallet.add-balance', compact('externalEnabled', 'purchaseUrl', 'tutorialVideo'));
    }

    public function redeemCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|min:10|max:5000',
        ]);

        $user = Auth::user();

        try {
            $result = $this->walletService->redeemCode($user->id, $request->code);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('wallet.add.balance')
            ->with('success', '$' . number_format($result['amount'], 2) . ' added to your wallet successfully! Your balance has been updated.');
    }
}
