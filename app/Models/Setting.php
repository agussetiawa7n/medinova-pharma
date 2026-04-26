<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $data = Cache::rememberForever("setting_{$key}", function () use ($key) {
            $setting = static::where('key', $key)->first();
            if (!$setting) return null;
            return ['value' => $setting->value, 'type' => $setting->type];
        });

        if (!$data) return $default;

        return match($data['type']) {
            'boolean' => (bool) $data['value'],
            'integer' => (int) $data['value'],
            'json'    => json_decode($data['value'], true),
            default   => $data['value'],
        };
    }

    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        $stored = is_array($value) ? json_encode($value) : (string) $value;

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'group' => $group, 'type' => $type]
        );

        Cache::forget("setting_{$key}");
    }
}
