<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    /**
     * Read a value from the settings store. Falls back to $default when missing.
     * Values are cached by the Setting model.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (!function_exists('money')) {
    /**
     * Format a numeric value as INR currency.
     */
    function money(float|int|string|null $amount, bool $withSymbol = true): string
    {
        $value = number_format((float) ($amount ?? 0), 2);
        return $withSymbol ? '₹' . $value : $value;
    }
}
