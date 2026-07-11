<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Composition extends Model
{
    protected $fillable = [
        'name', 'slug', 'overview', 'how_it_works', 'uses', 'side_effects',
        'precautions', 'medical_disclaimer', 'meta_title', 'meta_description',
        'content_status', 'content_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'content_reviewed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
