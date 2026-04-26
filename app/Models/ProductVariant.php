<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'name', 'sku', 'price', 'compare_price',
        'stock_quantity', 'is_default', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price'          => 'decimal:2',
            'compare_price'  => 'decimal:2',
            'is_default'     => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
