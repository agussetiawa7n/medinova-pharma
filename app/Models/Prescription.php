<?php

namespace App\Models;

use App\Enums\PrescriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Prescription extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id', 'order_id', 'file_path', 'file_name', 'mime_type',
        'status', 'admin_notes', 'patient_notes', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'file_path'    => 'encrypted',
            'status'       => PrescriptionStatus::class,
            'reviewed_at'  => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'admin_notes', 'patient_notes', 'reviewed_by', 'reviewed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', PrescriptionStatus::Pending);
    }
}
