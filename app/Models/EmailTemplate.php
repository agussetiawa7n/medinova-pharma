<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['key', 'label', 'subject', 'body', 'variables', 'is_active'];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public static function getByKey(string $key): ?self
    {
        return static::where('key', $key)->where('is_active', true)->first();
    }

    /**
     * Render the subject with given variables.
     */
    public function renderSubject(array $vars): string
    {
        return str_replace(array_keys($vars), array_values($vars), $this->subject);
    }

    /**
     * Render the body with given variables.
     */
    public function renderBody(array $vars): string
    {
        return str_replace(array_keys($vars), array_values($vars), $this->body);
    }
}
