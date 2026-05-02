<?php

namespace App\Enums;

use App\Concerns\HasLabel;

enum OrderStatus: string
{
    use HasLabel;
    case Pending    = 'pending';
    case Confirmed  = 'confirmed';
    case Processing = 'processing';
    case Shipped    = 'shipped';
    case Delivered  = 'delivered';
    case Cancelled  = 'cancelled';
    case Refunded   = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::Pending    => 'Pending',
            self::Confirmed  => 'Confirmed',
            self::Processing => 'Processing',
            self::Shipped    => 'Shipped',
            self::Delivered  => 'Delivered',
            self::Cancelled  => 'Cancelled',
            self::Refunded   => 'Refunded',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending    => 'warning',
            self::Confirmed  => 'info',
            self::Processing => 'info',
            self::Shipped    => 'primary',
            self::Delivered  => 'success',
            self::Cancelled  => 'danger',
            self::Refunded   => 'gray',
        };
    }
}
