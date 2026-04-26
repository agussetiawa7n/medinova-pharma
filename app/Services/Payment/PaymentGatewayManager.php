<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentMethod;
use App\Models\Setting;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /** @var array<string, callable> */
    private array $resolvers = [];

    /** @var array<string, PaymentGatewayInterface> */
    private array $resolved = [];

    public function __construct(
        callable $razorpay,
        callable $stripe,
        callable $paypal,
        callable $cod,
        callable $wallet,
    ) {
        $this->resolvers = [
            PaymentMethod::Razorpay->value      => $razorpay,
            PaymentMethod::Stripe->value        => $stripe,
            PaymentMethod::PayPal->value        => $paypal,
            PaymentMethod::COD->value           => $cod,
            PaymentMethod::Wallet->value        => $wallet,
            PaymentMethod::WalletPartial->value => $wallet,
        ];
    }

    public function driver(string|PaymentMethod $method): PaymentGatewayInterface
    {
        $key = $method instanceof PaymentMethod ? $method->value : $method;

        if (!isset($this->resolvers[$key])) {
            throw new InvalidArgumentException("Payment driver [{$key}] is not registered.");
        }

        return $this->resolved[$key] ??= ($this->resolvers[$key])();
    }

    /** @return PaymentGatewayInterface[] */
    public function enabledGateways(): array
    {
        $enabled = [];
        foreach ($this->resolvers as $key => $resolver) {
            $gateway = $this->resolved[$key] ??= $resolver();
            if ($gateway->isEnabled()) {
                $enabled[$key] = $gateway;
            }
        }
        return $enabled;
    }

    public function availableMethods(): array
    {
        return array_keys($this->enabledGateways());
    }
}
