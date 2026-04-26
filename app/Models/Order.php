<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number', 'user_id', 'coupon_id', 'status', 'payment_status',
        'payment_method', 'payment_gateway_id', 'subtotal', 'discount_amount',
        'shipping_amount', 'tax_amount', 'wallet_amount_used', 'total',
        'currency', 'currency_rate', 'shipping_name', 'shipping_phone',
        'shipping_address_line_1', 'shipping_address_line_2', 'shipping_city',
        'shipping_state', 'shipping_postal_code', 'shipping_country', 'notes',
        'tracking_number', 'tracking_url', 'paid_at', 'shipped_at',
        'delivered_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status'           => OrderStatus::class,
            'payment_status'   => PaymentStatus::class,
            'payment_method'   => PaymentMethod::class,
            'subtotal'         => 'decimal:2',
            'discount_amount'  => 'decimal:2',
            'shipping_amount'  => 'decimal:2',
            'tax_amount'       => 'decimal:2',
            'wallet_amount_used'=> 'decimal:2',
            'total'            => 'decimal:2',
            'paid_at'          => 'datetime',
            'shipped_at'       => 'datetime',
            'delivered_at'     => 'datetime',
            'cancelled_at'     => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = 'MNP-' . strtoupper(substr(uniqid(), -8));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', OrderStatus::Pending);
    }

    public function scopeProcessing($query)
    {
        return $query->whereIn('status', [OrderStatus::Confirmed, OrderStatus::Processing]);
    }
}
