<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Auth;

class WalletService
{
    public function getOrCreate(int $userId): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0, 'currency' => config('app.wallet_currency', 'MNP')]
        );
    }

    public function getBalance(int $userId): float
    {
        return $this->getOrCreate($userId)->balance;
    }

    public function credit(int $userId, float $amount, string $description = '', ?string $referenceType = null, ?int $referenceId = null): WalletTransaction
    {
        $wallet = $this->getOrCreate($userId);

        return $wallet->credit($amount, $description, $referenceType, $referenceId);
    }

    public function debit(int $userId, float $amount, string $description = '', ?string $referenceType = null, ?int $referenceId = null): WalletTransaction
    {
        $wallet = $this->getOrCreate($userId);

        if (!$wallet->hasSufficientBalance($amount)) {
            throw new \RuntimeException("Insufficient wallet balance. Available: {$wallet->balance}, Required: {$amount}");
        }

        return $wallet->debit($amount, $description, $referenceType, $referenceId);
    }

    public function hasSufficientBalance(int $userId, float $amount): bool
    {
        return $this->getOrCreate($userId)->hasSufficientBalance($amount);
    }

    public function getPurchaseUrl(): string
    {
        return \App\Models\Setting::get('wallet_purchase_url', config('app.wallet_purchase_url', '#'));
    }
}
