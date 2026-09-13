<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'pond_id',
        'device_id',
        'reading_id',
        'reported_by_user_id',
        'assigned_to_user_id',
        'resolved_by_user_id',
        'parameter',
        'value',
        'min_threshold',
        'max_threshold',
        'severity',
        'status',
        'message',
        'detected_at',
        'assigned_at',
        'resolved_at',
        'resolution_notes',
    ];

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function reading(): BelongsTo
    {
        return $this->belongsTo(Reading::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
