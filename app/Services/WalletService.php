<?php

namespace App\Services;

use App\Models\RedeemedCode;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Carbon;

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
        return Setting::get('wallet.purchase_url', config('app.wallet_purchase_url', '#'));
    }

    public function isExternalTopupEnabled(): bool
    {
        return (bool) Setting::get('wallet.external_enabled', false);
    }

    public function getTutorialVideoUrl(): string
    {
        return Setting::get('wallet.tutorial_video', '');
    }

    public function getSharedSecret(): string
    {
        return Setting::get('wallet.shared_secret', config('app.wallet_shared_secret', ''));
    }

    public function redeemCode(int $userId, string $code): array
    {
        $secret = $this->getSharedSecret();

        if (empty($secret)) {
            throw new \RuntimeException('Wallet top-up system is not configured. Please contact support.');
        }

        $data = $this->decodeAndVerify($code, $secret);

        if (!$data) {
            throw new \RuntimeException('Invalid or expired redemption code. Please check and try again.');
        }

        $codeHash = hash('sha256', $code);

        if (RedeemedCode::where('code_hash', $codeHash)->exists()) {
            throw new \RuntimeException('This code has already been redeemed.');
        }

        $amount = (float) $data['amount'];
        $transactionId = $data['transaction_id'];

        \DB::transaction(function () use ($userId, $amount, $transactionId, $codeHash) {
            $this->credit(
                $userId,
                $amount,
                "Wallet top-up via external purchase (Txn: {$transactionId})",
                'external',
                null
            );

            RedeemedCode::create([
                'user_id'        => $userId,
                'code_hash'      => $codeHash,
                'amount'         => $amount,
                'transaction_id' => $transactionId,
                'redeemed_at'     => now(),
            ]);
        });

        return [
            'success'        => true,
            'amount'         => $amount,
            'transaction_id' => $transactionId,
        ];
    }

    private function decodeAndVerify(string $code, string $secret): ?array
    {
        $decoded = base64_decode($code, true);
        if (!$decoded) {
            return null;
        }

        $data = json_decode($decoded, true);
        if (!is_array($data)
            || !isset($data['user_id'], $data['amount'], $data['transaction_id'], $data['timestamp'], $data['signature'])
        ) {
            return null;
        }

        $expectedSignature = hash_hmac(
            'sha256',
            implode(':', [$data['user_id'], $data['amount'], $data['transaction_id'], $data['timestamp']]),
            $secret
        );

        if (!hash_equals($expectedSignature, $data['signature'])) {
            return null;
        }

        // Codes expire after 7 days
        $codeTime = Carbon::createFromTimestamp($data['timestamp']);
        if ($codeTime->diffInDays(now()) > 7) {
            return null;
        }

        return $data;
    }
}
