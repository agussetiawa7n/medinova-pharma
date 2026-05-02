<?php

namespace App\Services\Payment\Concerns;

use App\Models\Setting;

trait HasGatewayToggle
{
    public function isEnabled(): bool
    {
        $key = strtolower(str_replace('Gateway', '', class_basename($this)));
        return Setting::get('payment.' . $key . '_enabled', true);
    }
}
