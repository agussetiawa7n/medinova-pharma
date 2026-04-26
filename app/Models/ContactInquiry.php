<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactInquiry extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message',
        'is_read', 'reply', 'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read'    => 'boolean',
            'replied_at' => 'datetime',
        ];
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
