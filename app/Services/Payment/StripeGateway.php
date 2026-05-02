<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Concerns\HasGatewayToggle;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;

class StripeGateway implements PaymentGatewayInterface
{
    use HasGatewayToggle;
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createPayment(float $amount, string $currency, array $metadata = []): array
    {
        $intent = PaymentIntent::create([
            'amount'             => (int) ($amount * 100),
            'currency'           => strtolower($currency),
            'metadata'           => $metadata,
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        return [
            'client_secret'    => $intent->client_secret,
            'payment_intent_id'=> $intent->id,
            'publishable_key'  => config('services.stripe.key'),
        ];
    }

    public function verifyPayment(array $payload): bool
    {
        try {
            $sig    = request()->header('Stripe-Signature');
            $secret = config('services.stripe.webhook_secret');

            \Stripe\Webhook::constructEvent(
                request()->getContent(), $sig, $secret
            );

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function refund(string $gatewayPaymentId, ?float $amount = null): bool
    {
        try {
            $params = ['payment_intent' => $gatewayPaymentId];
            if ($amount) {
                $params['amount'] = (int) ($amount * 100);
            }
            Refund::create($params);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function getName(): string
    {
        return 'Stripe';
    }
}
