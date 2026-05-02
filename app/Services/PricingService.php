<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Setting;

class PricingService
{
    public function calculate(Cart $cart, float $walletAmount = 0): array
    {
        $subtotal = $cart->items->sum(fn ($i) => round($i->unit_price * $i->quantity, 2));

        $coupon   = null;
        $discount = 0;
        if ($cart->coupon_code) {
            $coupon = \App\Models\Coupon::where('code', $cart->coupon_code)->first();
            if ($coupon) {
                $discount = $coupon->calculateDiscount($subtotal);
            }
        }

        $threshold = (int) Setting::get('pricing.free_shipping_threshold', 499);
        $fee       = (int) Setting::get('pricing.delivery_fee', 50);
        $shipping  = $subtotal > 0 && $subtotal < $threshold ? $fee : 0;

        $taxRate = (int) Setting::get('pricing.tax_rate', 18);
        $tax     = round(($subtotal - $discount) * ($taxRate / 100), 2);

        $total     = round($subtotal - $discount + $shipping + $tax - $walletAmount, 2);
        $grandTotal = max(0, $total);

        return [
            'subtotal'     => round($subtotal, 2),
            'discount'     => round($discount, 2),
            'coupon'       => $coupon,
            'shipping'     => $shipping,
            'tax'          => $tax,
            'walletAmount' => $walletAmount,
            'total'        => $total,
            'grandTotal'   => $grandTotal,
        ];
    }
}
