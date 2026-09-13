<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reading extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'pond_id',
        'temperature',
        'ph',
        'turbidity',
        'water_level',
        'recorded_at',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }
}
