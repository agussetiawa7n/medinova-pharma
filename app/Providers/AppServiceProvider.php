<?php

namespace App\Providers;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\Prescription;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Payment\CodGateway;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PayPalGateway;
use App\Services\Payment\RazorpayGateway;
use App\Services\Payment\StripeGateway;
use App\Services\Payment\WalletGateway;
use App\Services\PrescriptionService;
use App\Services\PricingService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('helpers.php');

        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            return new PaymentGatewayManager(
                fn () => $app->make(RazorpayGateway::class),
                fn () => $app->make(StripeGateway::class),
                fn () => $app->make(PayPalGateway::class),
                fn () => $app->make(CodGateway::class),
                fn () => $app->make(WalletGateway::class),
            );
        });

        $this->app->singleton(CartService::class);
        $this->app->singleton(WalletService::class);
        $this->app->singleton(PrescriptionService::class);

        $this->app->singleton(OrderService::class, function ($app) {
            return new OrderService(
                $app->make(CartService::class),
                $app->make(PaymentGatewayManager::class),
                $app->make(PricingService::class),
            );
        });

        // Single menu-categories query per request (shared by header + nav)
        $this->app->singleton('menuCategories', function () {
            return Category::where('show_in_menu', true)
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get();
        });
    }

    public function boot(): void
    {
        Gate::define('owns-order', fn ($user, Order $order) => $user->id === $order->user_id);

        Gate::define('owns-address', fn ($user, Address $address) => $user->id === $address->user_id);

        Gate::define('owns-prescription', fn ($user, Prescription $prescription) => $user->id === $prescription->user_id);
    }
}

