<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'description', 'type', 'value', 'min_order_amount',
        'max_discount_amount', 'usage_limit', 'usage_limit_per_user',
        'used_count', 'is_active', 'starts_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'value'              => 'decimal:2',
            'min_order_amount'   => 'decimal:2',
            'max_discount_amount'=> 'decimal:2',
            'is_active'          => 'boolean',
            'starts_at'          => 'datetime',
            'expires_at'         => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;

        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        $discount = match($this->type) {
            'percentage'   => $subtotal * ($this->value / 100),
            'fixed'        => $this->value,
            'free_shipping'=> 0,
            default        => 0,
        };

        if ($this->max_discount_amount) {
            $discount = min($discount, $this->max_discount_amount);
        }

        return min($discount, $subtotal);
    }
}
