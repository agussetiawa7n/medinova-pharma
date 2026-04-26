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

    public function mount(): void
    {
        $this->payment_razorpay_enabled = (bool) Setting::get('payment_razorpay_enabled', false);
        $this->payment_stripe_enabled   = (bool) Setting::get('payment_stripe_enabled', false);
        $this->payment_paypal_enabled   = (bool) Setting::get('payment_paypal_enabled', false);
        $this->payment_cod_enabled      = (bool) Setting::get('payment_cod_enabled', true);
        $this->payment_wallet_enabled   = (bool) Setting::get('payment_wallet_enabled', true);

        $this->razorpay_key    = Setting::get('razorpay_key', config('services.razorpay.key', '')) ?? '';
        $this->razorpay_secret = Setting::get('razorpay_secret', config('services.razorpay.secret', '')) ?? '';

        $this->stripe_key            = Setting::get('stripe_key', config('services.stripe.key', '')) ?? '';
        $this->stripe_secret         = Setting::get('stripe_secret', config('services.stripe.secret', '')) ?? '';
        $this->stripe_webhook_secret = Setting::get('stripe_webhook_secret', config('services.stripe.webhook_secret', '')) ?? '';

        $this->paypal_mode          = Setting::get('paypal_mode', 'sandbox') ?? 'sandbox';
        $this->paypal_client_id     = Setting::get('paypal_client_id', config('paypal.sandbox.client_id', '')) ?? '';
        $this->paypal_client_secret = Setting::get('paypal_client_secret', config('paypal.sandbox.client_secret', '')) ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'paypal_mode' => 'in:sandbox,live',
        ]);

        // Booleans
        foreach (['payment_razorpay_enabled', 'payment_stripe_enabled', 'payment_paypal_enabled', 'payment_cod_enabled', 'payment_wallet_enabled'] as $key) {
            Setting::set($key, (bool) $this->{$key}, 'payment', 'boolean');
        }

        // Strings
        foreach (['razorpay_key', 'razorpay_secret', 'stripe_key', 'stripe_secret', 'stripe_webhook_secret', 'paypal_mode', 'paypal_client_id', 'paypal_client_secret'] as $key) {
            Setting::set($key, (string) $this->{$key}, 'payment', 'string');
        }

        $this->syncEnv([
            'RAZORPAY_KEY'          => $this->razorpay_key,
            'RAZORPAY_SECRET'       => $this->razorpay_secret,
            'STRIPE_KEY'            => $this->stripe_key,
            'STRIPE_SECRET'         => $this->stripe_secret,
            'STRIPE_WEBHOOK_SECRET' => $this->stripe_webhook_secret,
            'PAYPAL_MODE'           => $this->paypal_mode,
            'PAYPAL_CLIENT_ID'      => $this->paypal_client_id,
            'PAYPAL_CLIENT_SECRET'  => $this->paypal_client_secret,
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
