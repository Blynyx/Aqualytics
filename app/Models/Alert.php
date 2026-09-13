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
        'parameter',
        'value',
        'min_threshold',
        'max_threshold',
        'severity',
        'status',
        'message',
        'detected_at',
        'resolved_at',
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
}
