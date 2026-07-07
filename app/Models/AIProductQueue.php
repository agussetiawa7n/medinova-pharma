<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIProductQueue extends Model
{
    protected $table = 'ai_product_queue';

    protected $fillable = [
        'type', 'product_name', 'generated_data', 'image_path', 'image_raw_url',
        'text_model_used', 'image_model_used', 'status', 'error_message',
        'retry_count', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'generated_data' => 'array',
        'approved_at'    => 'datetime',
    ];

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
