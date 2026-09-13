<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PondThreshold extends Model
{
    use HasFactory;

    protected $fillable = [
        'pond_id',
        'temperature_min',
        'temperature_max',
        'ph_min',
        'ph_max',
        'turbidity_max',
        'water_level_min',
        'water_level_max',
    ];

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }
}
