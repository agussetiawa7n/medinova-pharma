<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Create a payment intent/order for the given amount.
     *
     * @param  float   $amount   Amount in base currency units
     * @param  string  $currency ISO 4217 currency code (e.g. INR, USD)
     * @param  array   $metadata Extra data (order_id, user_id, etc.)
     * @return array   Gateway-specific response (client_secret, order_id, etc.)
     */
    public function createPayment(float $amount, string $currency, array $metadata = []): array;

    /**
     * Verify/capture a payment after the customer completes checkout.
     *
     * @param  array $payload Raw POST data from the gateway callback
     * @return bool
     */
    public function verifyPayment(array $payload): bool;

    /**
     * Issue a full or partial refund for a captured payment.
     *
     * @param  string $gatewayPaymentId  The gateway's payment/transaction ID
     * @param  float  $amount            Amount to refund (null = full refund)
     * @return bool
     */
    public function refund(string $gatewayPaymentId, ?float $amount = null): bool;

    /**
     * Human-readable name shown in the admin and checkout UI.
     */
    public function getName(): string;

    /**
     * Whether this gateway is enabled (from settings).
     */
    public function isEnabled(): bool;
}
