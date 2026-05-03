<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Concerns\HasGatewayToggle;
use Illuminate\Support\Facades\Log;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalGateway implements PaymentGatewayInterface
{
    use HasGatewayToggle;
    private ?PayPalClient $client = null;

    private function client(): PayPalClient
    {
        if ($this->client === null) {
            $this->client = new PayPalClient();
            $this->client->getAccessToken();
        }

        return $this->client;
    }

    public function createPayment(float $amount, string $currency, array $metadata = []): array
    {
        $order = $this->client()->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $currency,
                    'value'         => number_format($amount, 2, '.', ''),
                ],
                'custom_id' => $metadata['order_id'] ?? null,
            ]],
            'application_context' => [
                'return_url' => route('checkout.callback', 'paypal'),
                'cancel_url' => route('checkout.cancel', 'paypal'),
            ],
        ]);

        $approveUrl = collect($order['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? '';

        return [
            'paypal_order_id' => $order['id'] ?? '',
            'approve_url'     => $approveUrl,
        ];
    }

    public function verifyPayment(array $payload): bool
    {
        try {
            $orderId = $payload['token'] ?? '';
            $result  = $this->client()->capturePaymentOrder($orderId);

            return ($result['status'] ?? '') === 'COMPLETED';
        } catch (\Exception $e) {
            Log::error('PayPal payment verification failed', [
                'gateway' => 'paypal',
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function refund(string $gatewayPaymentId, ?float $amount = null): bool
    {
        try {
            $this->client()->refundCapturedPayment(
                $gatewayPaymentId,
                '',
                $amount ?? 0.0,
                'Refund'
            );

            return true;
        } catch (\Exception $e) {
            Log::error('PayPal refund failed', [
                'gateway' => 'paypal',
                'payment_id' => $gatewayPaymentId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getName(): string
    {
        return 'PayPal';
    }
}
