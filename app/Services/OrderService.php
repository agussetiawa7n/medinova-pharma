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
    ) {}

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $cart     = $this->cartService->getCart();
            $coupon   = $cart->coupon;
            $subtotal = $cart->subtotal;

            $discount  = $coupon ? $coupon->calculateDiscount($subtotal) : 0;
            $shipping  = $this->calculateShipping($data);
            $tax       = $this->calculateTax($subtotal - $discount);
            $walletAmt = $data['wallet_amount'] ?? 0;
            $total     = max(0, $subtotal - $discount + $shipping + $tax - $walletAmt);

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
                    if ($item->variant) {
                        $item->variant->decrement('stock_quantity', $item->quantity);
                    } else {
                        $item->product->decrement('stock_quantity', $item->quantity);
                    }
                }
            }

            // Debit wallet if used
            if ($walletAmt > 0) {
                $wallet = Wallet::where('user_id', Auth::id())->first();
                $wallet?->debit($walletAmt, 'Order payment', 'order', $order->id);
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
            if ($item->variant_id) {
                \App\Models\ProductVariant::find($item->product_variant_id)?->increment('stock_quantity', $item->quantity);
            } else {
                $item->product?->increment('stock_quantity', $item->quantity);
            }
        }

        // Refund wallet amount if applicable
        if ($order->wallet_amount_used > 0) {
            $wallet = Wallet::where('user_id', $order->user_id)->first();
            $wallet?->credit($order->wallet_amount_used, 'Refund for cancelled order #' . $order->order_number, 'order', $order->id);
        }
    }

    private function calculateShipping(array $data): float
    {
        return \App\Models\Setting::get('shipping_flat_rate', 50);
    }

    private function calculateTax(float $amount): float
    {
        $rate = \App\Models\Setting::get('tax_rate', 0) / 100;

        return round($amount * $rate, 2);
    }
}
