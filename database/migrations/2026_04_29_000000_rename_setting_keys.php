<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $renames = [
            'site.free_shipping_threshold' => 'pricing.free_shipping_threshold',
            'site.delivery_fee'            => 'pricing.delivery_fee',
            'tax_rate'                     => 'pricing.tax_rate',
            'payment_razorpay_enabled'     => 'payment.razorpay_enabled',
            'payment_stripe_enabled'       => 'payment.stripe_enabled',
            'payment_paypal_enabled'       => 'payment.paypal_enabled',
            'payment_cod_enabled'          => 'payment.cod_enabled',
            'payment_wallet_enabled'       => 'payment.wallet_enabled',
            'wallet_purchase_url'          => 'wallet.purchase_url',
            'wallet_external_enabled'      => 'wallet.external_enabled',
            'wallet_tutorial_video'        => 'wallet.tutorial_video',
            'wallet_shared_secret'         => 'wallet.shared_secret',
        ];

        foreach ($renames as $old => $new) {
            Setting::where('key', $old)->update(['key' => $new]);
        }

        // Delete orphaned/duplicate keys
        $orphaned = [
            'shipping_flat_rate',
            'free_shipping_threshold',
            'razorpay_enabled',
            'stripe_enabled',
            'paypal_enabled',
            'cod_enabled',
            'wallet_enabled',
        ];

        Setting::whereIn('key', $orphaned)->delete();

        // Clear settings cache
        \Illuminate\Support\Facades\Cache::flush();
    }

    public function down(): void
    {
        $reverts = [
            'pricing.free_shipping_threshold' => 'site.free_shipping_threshold',
            'pricing.delivery_fee'            => 'site.delivery_fee',
            'pricing.tax_rate'                => 'tax_rate',
            'payment.razorpay_enabled'        => 'payment_razorpay_enabled',
            'payment.stripe_enabled'          => 'payment_stripe_enabled',
            'payment.paypal_enabled'          => 'payment_paypal_enabled',
            'payment.cod_enabled'             => 'payment_cod_enabled',
            'payment.wallet_enabled'          => 'payment_wallet_enabled',
            'wallet.purchase_url'             => 'wallet_purchase_url',
            'wallet.external_enabled'         => 'wallet_external_enabled',
            'wallet.tutorial_video'           => 'wallet_tutorial_video',
            'wallet.shared_secret'            => 'wallet_shared_secret',
        ];

        foreach ($reverts as $new => $old) {
            Setting::where('key', $new)->update(['key' => $old]);
        }

        \Illuminate\Support\Facades\Cache::flush();
    }
};
