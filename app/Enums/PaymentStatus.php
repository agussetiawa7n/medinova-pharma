<?php

namespace App\Enums;

use App\Concerns\HasLabel;

enum PaymentStatus: string
{
    use HasLabel;
    case Pending            = 'pending';
    case Paid               = 'paid';
    case Failed             = 'failed';
    case Refunded           = 'refunded';
    case PartiallyRefunded  = 'partially_refunded';

    public function label(): string
    {
        return match($this) {
            self::Pending           => 'Pending',
            self::Paid              => 'Paid',
            self::Failed            => 'Failed',
            self::Refunded          => 'Refunded',
            self::PartiallyRefunded => 'Partially Refunded',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending           => 'warning',
            self::Paid              => 'success',
            self::Failed            => 'danger',
            self::Refunded          => 'gray',
            self::PartiallyRefunded => 'info',
        };
    }
}
