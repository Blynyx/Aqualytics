<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    public const CODE_HOME = 'home';

    public const CODE_FARM = 'farm';

    protected $fillable = [
        'code',
        'name',
        'account_type',
        'monthly_price',
        'max_units',
        'max_users',
        'max_devices',
        'history_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'max_units' => 'integer',
            'max_users' => 'integer',
            'max_devices' => 'integer',
            'history_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
