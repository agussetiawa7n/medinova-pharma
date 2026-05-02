<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Wallet;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly CartService            $cartService,
        private readonly PaymentGatewayManager  $paymentManager,
        private readonly PricingService         $pricingService,
    ) {}

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $cart      = $this->cartService->getCart();
            $coupon    = $cart->coupon;
            $walletAmt = $data['wallet_amount'] ?? 0;

            // Re-validate coupon at order time
            if ($coupon && !$coupon->isValid()) {
                $cart->update(['coupon_code' => null]);
                $coupon = null;
            }

            // For full wallet payment: compute full amount first, then use it
            $isFullWallet = ($data['payment_method'] === PaymentMethod::Wallet);
            if ($isFullWallet) {
                $prePricing = $this->pricingService->calculate($cart, 0);
                // Full order amount = subtotal - discount + shipping + tax
                $walletAmt = $prePricing['subtotal'] - $prePricing['discount']
                    + $prePricing['shipping'] + $prePricing['tax'];
            }

            $pricing  = $this->pricingService->calculate($cart, $walletAmt);
            $subtotal = $pricing['subtotal'];
            $discount = $pricing['discount'];
            $shipping = $pricing['shipping'];
            $tax      = $pricing['tax'];
            $total    = $pricing['grandTotal'];

            $order = Order::create([
                'user_id'                => Auth::id(),
                'coupon_id'              => $coupon?->id,
                'status'                 => OrderStatus::Pending,
                'payment_status'         => PaymentStatus::Pending,
                'payment_method'         => $data['payment_method'],
                'subtotal'               => $subtotal,
                'discount_amount'        => $discount,
                'shipping_amount'        => $shipping,
                'tax_amount'             => $tax,
                'wallet_amount_used'     => $walletAmt,
                'total'                  => $total,
                'currency'               => session('currency', 'INR'),
                'shipping_name'          => $data['shipping_name'],
                'shipping_phone'         => $data['shipping_phone'],
                'shipping_address_line_1'=> $data['shipping_address_line_1'],
                'shipping_address_line_2'=> $data['shipping_address_line_2'] ?? null,
                'shipping_city'          => $data['shipping_city'],
                'shipping_state'         => $data['shipping_state'],
                'shipping_postal_code'   => $data['shipping_postal_code'],
                'shipping_country'       => $data['shipping_country'] ?? 'India',
                'notes'                  => $data['notes'] ?? null,
            ]);

            // Create order items and decrement stock
            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id'          => $item->product_id,
                    'product_variant_id'  => $item->product_variant_id,
                    'product_name'        => $item->product->name,
                    'variant_name'        => $item->variant?->name,
                    'product_sku'         => $item->variant?->sku ?? $item->product->sku,
                    'quantity'            => $item->quantity,
                    'unit_price'          => $item->unit_price,
                    'total'               => $item->unit_price * $item->quantity,
                    'requires_prescription'=> $item->product->requires_prescription,
                ]);

                if ($item->product->track_inventory) {
                    $this->adjustStock($item, 'decrement');
                }
            }

            // Debit wallet if used
            if ($walletAmt > 0) {
                $wallet = Wallet::where('user_id', Auth::id())->first();
                if (!$wallet || !$wallet->hasSufficientBalance($walletAmt)) {
                    throw new \RuntimeException('Insufficient wallet balance.');
                }
                $wallet->debit($walletAmt, 'Order payment', 'order', $order->id);
            }

            // Increment coupon usage
            $coupon?->increment('used_count');

            // Clear cart
            $this->cartService->clearCart();

            // Record initial status
            $order->statusHistories()->create([
                'status'     => OrderStatus::Pending->value,
                'note'       => 'Order placed.',
                'created_by' => Auth::id(),
            ]);

            return $order;
        });
    }

    public function markPaid(Order $order, string $gatewayPaymentId): void
    {
        $order->update([
            'payment_status'     => PaymentStatus::Paid,
            'payment_gateway_id' => $gatewayPaymentId,
            'status'             => OrderStatus::Confirmed,
            'paid_at'            => now(),
        ]);

        $order->statusHistories()->create([
            'status' => OrderStatus::Confirmed->value,
            'note'   => 'Payment received via ' . $order->payment_method->label(),
        ]);
    }

    public function updateStatus(Order $order, string $status, string $note = '', ?int $adminId = null): void
    {
        $order->update(['status' => $status]);

        $timestamps = [
            OrderStatus::Shipped->value   => 'shipped_at',
            OrderStatus::Delivered->value => 'delivered_at',
            OrderStatus::Cancelled->value => 'cancelled_at',
        ];

        if (isset($timestamps[$status])) {
            $order->update([$timestamps[$status] => now()]);
        }

        $order->statusHistories()->create([
            'status'     => $status,
            'note'       => $note,
            'created_by' => $adminId ?? Auth::id(),
        ]);
    }

    public function cancelOrder(Order $order, string $reason = ''): void
    {
        $this->updateStatus($order, OrderStatus::Cancelled->value, $reason ?: 'Order cancelled.');

        // Restore stock
        foreach ($order->items as $item) {
            $this->adjustStock($item, 'increment');
        }

        // Refund wallet amount if applicable
        if ($order->wallet_amount_used > 0) {
            $wallet = Wallet::where('user_id', $order->user_id)->first();
            $wallet?->credit($order->wallet_amount_used, 'Refund for cancelled order #' . $order->order_number, 'order', $order->id);
        }
    }

    private function adjustStock($item, string $direction): void
    {
        $target = $item->variant ?? $item->product;

        if (!$target) return;

        if ($direction === 'increment') {
            $target->increment('stock_quantity', $item->quantity);
        } else {
            $target->decrement('stock_quantity', $item->quantity);
        }
    }

}
