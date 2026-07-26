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
        'retry_count', 'locked_at', 'dedupe_key', 'approved_by', 'approved_at',
    ];

    /** Terminal states: kept as history, exempt from the live-row unique index. */
    private const TERMINAL_STATUSES = ['saved', 'skipped'];

    protected static function booted(): void
    {
        // Maintain the key the unique index is built on, so no caller has to
        // remember it. A live row is unique per (type, normalised name); once a
        // row reaches a terminal state it drops out of the constraint.
        $sync = function (self $item): void {
            $item->dedupe_key = in_array($item->status, self::TERMINAL_STATUSES, true)
                ? null
                : $item->type . ':' . mb_strtolower(trim((string) $item->product_name));
        };

        static::creating($sync);
        static::updating($sync);
    }

    protected $casts = [
        'generated_data' => 'array',
        'approved_at'    => 'datetime',
        'locked_at'      => 'datetime',
    ];

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
