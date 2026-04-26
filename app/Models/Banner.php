<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'image', 'mobile_image', 'button_text', 'button_url',
        'badge_text', 'position', 'is_active', 'sort_order', 'starts_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'starts_at'  => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(function () {
            $path = $this->image;
            if (!$path) return null;
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
            return Storage::url($path);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    public function scopePosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    public function scopeHero($query)
    {
        return $query->where('position', 'hero');
    }

    public function scopePromo($query)
    {
        return $query->where('position', 'promo');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
