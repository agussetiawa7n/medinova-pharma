<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Concerns\HasGatewayToggle;
use App\Models\Wallet;

class WalletGateway implements PaymentGatewayInterface
{
    use HasGatewayToggle;
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
        // Wallet debit already happens in OrderService::createOrder().
        // This gateway is never called through handlePaymentCallback() —
        // CheckoutController routes Wallet/WalletPartial directly to markPaid().
        // Returning true prevents a double-debit if the callback path is ever reached.
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
}
