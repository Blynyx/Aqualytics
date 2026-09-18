<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pond extends Model
{
    use HasFactory;

    public const TYPE_AQUARIUM = 'aquarium';

    public const TYPE_POND = 'pond';

    protected $fillable = [
        'fish_farm_id',
        'user_id',
        'name',
        'code',
        'species',
        'location',
        'status',
        'unit_type',
    ];

    protected static function booted(): void
    {
        static::creating(function (Pond $pond): void {
            if ($pond->fish_farm_id === null && $pond->user_id !== null) {
                $pond->fish_farm_id = User::find($pond->user_id)?->fish_farm_id;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fishFarm(): BelongsTo
    {
        return $this->belongsTo(FishFarm::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class);
    }

    public function threshold(): HasOne
    {
        return $this->hasOne(PondThreshold::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}