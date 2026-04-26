<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\Order;

class WalletGateway implements PaymentGatewayInterface
{
    public function createPayment(float $amount, string $currency, array $metadata = []): array
    {
        $userId = $metadata['user_id'] ?? null;
        $wallet = $userId ? Wallet::where('user_id', $userId)->first() : null;

        return [
            'method'          => 'wallet',
            'amount'          => $amount,
            'wallet_balance'  => $wallet?->balance ?? 0,
            'sufficient'      => $wallet ? $wallet->hasSufficientBalance($amount) : false,
        ];
    }

    public function verifyPayment(array $payload): bool
    {
        $userId  = $payload['user_id'] ?? null;
        $amount  = $payload['amount'] ?? 0;
        $orderId = $payload['order_id'] ?? null;

        if (!$userId || !$amount) return false;

        $wallet = Wallet::where('user_id', $userId)->first();

        if (!$wallet || !$wallet->hasSufficientBalance($amount)) {
            return false;
        }

        $wallet->debit($amount, 'Order payment', 'order', $orderId);

        return true;
    }

    public function refund(string $gatewayPaymentId, ?float $amount = null): bool
    {
        // Wallet refund is handled in OrderService (credit back to wallet)
        return true;
    }

    public function getName(): string
    {
        return 'Wallet';
    }

    public function isEnabled(): bool
    {
        return (bool) Setting::get('payment_wallet_enabled', true);
    }
}
