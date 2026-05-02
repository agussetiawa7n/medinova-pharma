<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Concerns\HasGatewayToggle;

class CodGateway implements PaymentGatewayInterface
{
    use HasGatewayToggle;
    public function createPayment(float $amount, string $currency, array $metadata = []): array
    {
        return ['method' => 'cod', 'amount' => $amount];
    }

    public function verifyPayment(array $payload): bool
    {
        // COD is always "verified" at placement; actual payment on delivery
        return true;
    }

    public function refund(string $gatewayPaymentId, ?float $amount = null): bool
    {
        // COD refunds are handled manually
        return true;
    }

    public function getName(): string
    {
        return 'Cash on Delivery';
    }
}
