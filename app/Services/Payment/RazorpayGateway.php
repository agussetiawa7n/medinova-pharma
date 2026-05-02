<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Concerns\HasGatewayToggle;
use Razorpay\Api\Api;

class RazorpayGateway implements PaymentGatewayInterface
{
    use HasGatewayToggle;
    private Api $api;

    public function __construct()
    {
        $this->api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
    }

    public function createPayment(float $amount, string $currency, array $metadata = []): array
    {
        $order = $this->api->order->create([
            'amount'   => (int) ($amount * 100), // paise
            'currency' => $currency,
            'receipt'  => 'MNP-' . ($metadata['order_id'] ?? uniqid()),
            'notes'    => $metadata,
        ]);

        return [
            'gateway_order_id' => $order->id,
            'key'              => config('services.razorpay.key'),
            'amount'           => $amount,
            'currency'         => $currency,
        ];
    }

    public function verifyPayment(array $payload): bool
    {
        try {
            $attributes = [
                'razorpay_order_id'   => $payload['razorpay_order_id'] ?? '',
                'razorpay_payment_id' => $payload['razorpay_payment_id'] ?? '',
                'razorpay_signature'  => $payload['razorpay_signature'] ?? '',
            ];

            $this->api->utility->verifyPaymentSignature($attributes);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function refund(string $gatewayPaymentId, ?float $amount = null): bool
    {
        try {
            $params = $amount ? ['amount' => (int) ($amount * 100)] : [];
            $this->api->payment->fetch($gatewayPaymentId)->refund($params);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function getName(): string
    {
        return 'Razorpay';
    }
}
