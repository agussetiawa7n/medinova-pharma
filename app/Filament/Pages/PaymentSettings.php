<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PaymentSettings extends Page
{
    protected string $view = 'filament.pages.payment-settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Payment Methods';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Payment Settings';

    // Gateway toggles
    public bool $payment_razorpay_enabled = false;
    public bool $payment_stripe_enabled   = false;
    public bool $payment_paypal_enabled   = false;
    public bool $payment_cod_enabled      = true;
    public bool $payment_wallet_enabled   = true;

    // Public keys (non-sensitive — safe to store in DB)
    public string $razorpay_key    = '';
    public string $stripe_key      = '';
    public string $paypal_mode     = 'sandbox';
    public string $paypal_client_id = '';

    // Wallet external purchase (non-sensitive)
    public bool $wallet_external_enabled = false;
    public string $wallet_purchase_url   = '';
    public string $wallet_tutorial_video = '';

    public function mount(): void
    {
        $this->payment_razorpay_enabled = (bool) Setting::get('payment.razorpay_enabled', false);
        $this->payment_stripe_enabled   = (bool) Setting::get('payment.stripe_enabled', false);
        $this->payment_paypal_enabled   = (bool) Setting::get('payment.paypal_enabled', false);
        $this->payment_cod_enabled      = (bool) Setting::get('payment.cod_enabled', true);
        $this->payment_wallet_enabled   = (bool) Setting::get('payment.wallet_enabled', true);

        $this->razorpay_key     = Setting::get('razorpay_key', config('services.razorpay.key', '')) ?? '';
        $this->stripe_key       = Setting::get('stripe_key', config('services.stripe.key', '')) ?? '';
        $this->paypal_mode      = Setting::get('paypal_mode', 'sandbox') ?? 'sandbox';
        $this->paypal_client_id = Setting::get('paypal_client_id', config('paypal.sandbox.client_id', '')) ?? '';

        $this->wallet_external_enabled = (bool) Setting::get('wallet.external_enabled', false);
        $this->wallet_purchase_url     = Setting::get('wallet.purchase_url', '') ?? '';
        $this->wallet_tutorial_video   = Setting::get('wallet.tutorial_video', '') ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'paypal_mode' => 'in:sandbox,live',
        ]);

        // Toggles
        $boolKeys = [
            'payment_razorpay_enabled' => 'payment.razorpay_enabled',
            'payment_stripe_enabled'   => 'payment.stripe_enabled',
            'payment_paypal_enabled'   => 'payment.paypal_enabled',
            'payment_cod_enabled'      => 'payment.cod_enabled',
            'payment_wallet_enabled'   => 'payment.wallet_enabled',
            'wallet_external_enabled'  => 'wallet.external_enabled',
        ];
        foreach ($boolKeys as $prop => $dbKey) {
            Setting::set($dbKey, (bool) $this->{$prop}, 'payment', 'boolean');
        }

        // Public keys (non-sensitive)
        $strKeys = ['razorpay_key', 'stripe_key', 'paypal_mode', 'paypal_client_id'];
        foreach ($strKeys as $key) {
            Setting::set($key, (string) $this->{$key}, 'payment', 'string');
        }

        // Wallet non-sensitive fields
        $walletKeys = [
            'wallet_purchase_url'  => 'wallet.purchase_url',
            'wallet_tutorial_video'=> 'wallet.tutorial_video',
        ];
        foreach ($walletKeys as $prop => $dbKey) {
            Setting::set($dbKey, (string) $this->{$prop}, 'payment', 'string');
        }

        // SECRETS: NEVER stored in DB. Configured via .env only.
        // RAZORPAY_SECRET, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET,
        // PAYPAL_CLIENT_SECRET, WALLET_SHARED_SECRET → .env

        Notification::make()
            ->title('Payment settings saved successfully.')
            ->success()
            ->send();
    }
}
