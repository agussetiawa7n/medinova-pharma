<?php

namespace App\Enums;

use App\Concerns\HasLabel;

enum PaymentMethod: string
{
    use HasLabel;
    case Razorpay      = 'razorpay';
    case Stripe        = 'stripe';
    case PayPal        = 'paypal';
    case COD           = 'cod';
    case Wallet        = 'wallet';
    case WalletPartial = 'wallet_partial';

    public function label(): string
    {
        return match($this) {
            self::Razorpay      => 'Razorpay',
            self::Stripe        => 'Stripe',
            self::PayPal        => 'PayPal',
            self::COD           => 'Cash on Delivery',
            self::Wallet        => 'Wallet',
            self::WalletPartial => 'Wallet + Gateway',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Razorpay      => 'heroicon-o-credit-card',
            self::Stripe        => 'heroicon-o-credit-card',
            self::PayPal        => 'heroicon-o-globe-alt',
            self::COD           => 'heroicon-o-banknotes',
            self::Wallet        => 'heroicon-o-wallet',
            self::WalletPartial => 'heroicon-o-wallet',
        };
    }
}
