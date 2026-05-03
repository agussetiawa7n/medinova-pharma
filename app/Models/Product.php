<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'sku', 'short_description', 'description',
        'thumbnail', 'price', 'compare_price', 'cost_price', 'stock_quantity', 'low_stock_threshold',
        'track_inventory', 'allow_backorder', 'weight', 'unit', 'requires_prescription',
        'is_active', 'is_featured', 'is_new_arrival', 'is_best_seller', 'images', 'tags',
        'manufacturer', 'composition', 'storage_conditions', 'expiry_date', 'views',
        'sort_order', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'price'                  => 'decimal:2',
            'compare_price'          => 'decimal:2',
            'cost_price'             => 'decimal:2',
            'weight'                 => 'decimal:2',
            'images'                 => 'array',
            'tags'                   => 'array',
            'expiry_date'            => 'date',
            'track_inventory'        => 'boolean',
            'allow_backorder'        => 'boolean',
            'requires_prescription'  => 'boolean',
            'is_active'              => 'boolean',
            'is_featured'            => 'boolean',
            'is_new_arrival'         => 'boolean',
            'is_best_seller'         => 'boolean',
        ];
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(function () {
            $path = $this->thumbnail;
            if (!$path) return asset('images/product-placeholder.svg');
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
            return Storage::disk('public')->url($path);
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function defaultVariant()
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->where('is_approved', true);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->where('is_approved', true);
    }

    public function allReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    public function scopeNewArrivals($query)
    {
        return $query->where('is_new_arrival', true)->where('is_active', true);
    }

    public function scopeBestSellers($query)
    {
        return $query->where('is_best_seller', true)->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                     ->where('stock_quantity', '>', 0);
    }

    // Helpers
    protected function discountPercent(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(function () {
            if (!$this->compare_price || $this->compare_price <= $this->price) {
                return 0;
            }
            return (int) round((($this->compare_price - $this->price) / $this->compare_price) * 100);
        });
    }

    protected function isInStock(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(function () {
            if (!$this->track_inventory) return true;
            return $this->stock_quantity > 0 || $this->allow_backorder;
        });
    }

    protected function stock(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(fn () => (int) $this->stock_quantity);
    }

    protected function averageRating(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(function () {
            // Use pre-loaded aggregate if available — prevents N+1 per product
            if (array_key_exists('reviews_avg_rating', $this->attributes)) {
                return round((float) ($this->attributes['reviews_avg_rating'] ?? 0), 1);
            }
            return round($this->reviews()->avg('rating') ?? 0, 1);
        });
    }
}
