<?php

namespace App\Enums;

enum PaymentStatus: string
{
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

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn ($case) => [$case->value => $case->label()]
        )->toArray();
    }
}
