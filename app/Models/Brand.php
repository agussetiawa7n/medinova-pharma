<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Brand extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'logo', 'website', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /** Values a model returns when it does not actually know the brand. */
    private const PLACEHOLDER_NAMES = [
        'unknown', 'n/a', 'na', 'none', 'not specified', 'not available',
        'generic', 'various', 'null', '-', '--',
    ];

    /**
     * Resolve an AI-supplied brand to a Brand row, creating it when genuinely new.
     *
     * Brands cannot realistically be pre-seeded, so auto-creation stays — but the
     * raw value needs cleaning first. Previously firstOrCreate(['name' => $raw])
     * ran on whatever came back, so "iverheal", "Iverheal " and "Iverheal 6mg"
     * each became their own row, and "Unknown" became a brand of its own.
     *
     * Returns null when the value carries no real brand.
     */
    public static function resolveFromAi(?string $raw): ?self
    {
        $name = self::normaliseName($raw);

        if ($name === null) {
            return null;
        }

        // Case-insensitive match so casing differences reuse the existing row
        // instead of creating a near-duplicate.
        $existing = static::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first()
            ?? static::where('slug', Str::slug($name))->first();

        if ($existing) {
            return $existing;
        }

        return static::create([
            'name'      => $name,
            'slug'      => Str::slug($name),
            'is_active' => true,
        ]);
    }

    /**
     * Trim, collapse whitespace and drop the strength/form suffix so the brand
     * is the brand — "Iverheal 6mg Tablet" becomes "Iverheal".
     */
    private static function normaliseName(?string $raw): ?string
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string) $raw));

        if ($name === '' || in_array(mb_strtolower($name), self::PLACEHOLDER_NAMES, true)) {
            return null;
        }

        // Cut everything from the first strength or dosage-form token onwards.
        $name = preg_replace(
            '/\s+\d+\s*(mg|mcg|ml|g|iu|%)\b.*$/iu',
            '',
            $name
        );
        $name = preg_replace(
            '/\s+(tablet|tablets|capsule|capsules|syrup|injection|cream|gel|ointment|oral jelly|strip|sachet|vial|drops?)\b.*$/iu',
            '',
            $name
        );

        $name = trim($name, " \t\n\r\0\x0B-–—,;:");

        // A bare number or a single character is not a brand.
        if ($name === '' || mb_strlen($name) < 2 || preg_match('/^\d+$/', $name)) {
            return null;
        }

        return $name;
    }
}
