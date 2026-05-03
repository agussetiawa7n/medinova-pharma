<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type'];

    /**
     * Bulk-cache ALL settings and return by key. Cuts N individual
     * Setting::get() calls into a single DB query + in-memory array lookups.
     */
    private static ?array $localCache = null;

    public static function getCached(?string $key = null, mixed $default = null): mixed
    {
        $all = self::$localCache ?? Cache::remember('site_settings_all', 3600, function () {
            return static::pluck('value', 'key')->toArray();
        });

        self::$localCache = $all;

        if ($key === null) {
            return $all;
        }

        if (!array_key_exists($key, $all)) {
            return $default;
        }

        $raw = $all[$key];

        // Try to resolve the type from a parallel type map
        $types = Cache::remember('site_settings_types', 3600, function () {
            return static::pluck('type', 'key')->toArray();
        });

        $type = $types[$key] ?? 'string';

        return match ($type) {
            'boolean' => (bool) $raw,
            'integer' => (int) $raw,
            'json'    => json_decode($raw, true),
            default   => $raw,
        };
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::getCached($key, $default);
    }

    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        $stored = is_array($value) ? json_encode($value) : (string) $value;

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'group' => $group, 'type' => $type]
        );

        Cache::forget("setting_{$key}");
        Cache::forget('site_settings_all');
        Cache::forget('site_settings_types');
        // Clear static in-memory cache so current request sees the update
        self::$localCache = null;
    }
}
