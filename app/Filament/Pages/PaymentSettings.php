<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Validate;

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

    // Razorpay
    public string $razorpay_key    = '';
    public string $razorpay_secret = '';

    // Stripe
    public string $stripe_key            = '';
    public string $stripe_secret         = '';
    public string $stripe_webhook_secret = '';

    // PayPal
    public string $paypal_mode          = 'sandbox';
    public string $paypal_client_id     = '';
    public string $paypal_client_secret = '';

    // External Wallet Purchase
    public bool $wallet_external_enabled = false;
    public string $wallet_purchase_url   = '';
    public string $wallet_shared_secret  = '';
    public string $wallet_tutorial_video = '';

    public function mount(): void
    {
        $this->payment_razorpay_enabled = (bool) Setting::get('payment.razorpay_enabled', false);
        $this->payment_stripe_enabled   = (bool) Setting::get('payment.stripe_enabled', false);
        $this->payment_paypal_enabled   = (bool) Setting::get('payment.paypal_enabled', false);
        $this->payment_cod_enabled      = (bool) Setting::get('payment.cod_enabled', true);
        $this->payment_wallet_enabled   = (bool) Setting::get('payment.wallet_enabled', true);

        $this->razorpay_key    = Setting::get('razorpay_key', config('services.razorpay.key', '')) ?? '';
        $this->razorpay_secret = Setting::get('razorpay_secret', config('services.razorpay.secret', '')) ?? '';

        $this->stripe_key            = Setting::get('stripe_key', config('services.stripe.key', '')) ?? '';
        $this->stripe_secret         = Setting::get('stripe_secret', config('services.stripe.secret', '')) ?? '';
        $this->stripe_webhook_secret = Setting::get('stripe_webhook_secret', config('services.stripe.webhook_secret', '')) ?? '';

        $this->paypal_mode          = Setting::get('paypal_mode', 'sandbox') ?? 'sandbox';
        $this->paypal_client_id     = Setting::get('paypal_client_id', config('paypal.sandbox.client_id', '')) ?? '';
        $this->paypal_client_secret = Setting::get('paypal_client_secret', config('paypal.sandbox.client_secret', '')) ?? '';

        $this->wallet_external_enabled = (bool) Setting::get('wallet.external_enabled', false);
        $this->wallet_purchase_url     = Setting::get('wallet.purchase_url', '') ?? '';
        $this->wallet_shared_secret    = Setting::get('wallet.shared_secret', '') ?? '';
        $this->wallet_tutorial_video   = Setting::get('wallet.tutorial_video', '') ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'paypal_mode' => 'in:sandbox,live',
        ]);

        // Booleans
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

        // Strings
        $strKeys = [
            'razorpay_key', 'razorpay_secret', 'stripe_key', 'stripe_secret',
            'stripe_webhook_secret', 'paypal_mode', 'paypal_client_id', 'paypal_client_secret',
        ];
        foreach ($strKeys as $key) {
            Setting::set($key, (string) $this->{$key}, 'payment', 'string');
        }
        $walletStrKeys = [
            'wallet_purchase_url'  => 'wallet.purchase_url',
            'wallet_shared_secret' => 'wallet.shared_secret',
            'wallet_tutorial_video'=> 'wallet.tutorial_video',
        ];
        foreach ($walletStrKeys as $prop => $dbKey) {
            Setting::set($dbKey, (string) $this->{$prop}, 'payment', 'string');
        }

        $this->syncEnv([
            'RAZORPAY_KEY'            => $this->razorpay_key,
            'RAZORPAY_SECRET'         => $this->razorpay_secret,
            'STRIPE_KEY'              => $this->stripe_key,
            'STRIPE_SECRET'           => $this->stripe_secret,
            'STRIPE_WEBHOOK_SECRET'   => $this->stripe_webhook_secret,
            'PAYPAL_MODE'             => $this->paypal_mode,
            'PAYPAL_CLIENT_ID'        => $this->paypal_client_id,
            'PAYPAL_CLIENT_SECRET'    => $this->paypal_client_secret,
            'WALLET_PURCHASE_URL'     => $this->wallet_purchase_url,
            'WALLET_SHARED_SECRET'    => $this->wallet_shared_secret,
            'WALLET_TUTORIAL_VIDEO'   => $this->wallet_tutorial_video,
        ]);

        Artisan::call('config:clear');

        Notification::make()
            ->title('Payment settings saved successfully.')
            ->success()
            ->send();
    }

    private function syncEnv(array $values): void
    {
        $envPath = base_path('.env');
        $content = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $value = preg_replace('/\s+/', '', (string) $value);
            if (preg_match("/^{$key}=.*$/m", $content)) {
                $content = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
