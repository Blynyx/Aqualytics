<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FishFarm extends Model
{
    use HasFactory;

    public const TYPE_HOME = 'home';

    public const TYPE_FARM = 'farm';

    protected $fillable = [
        'name',
        'status',
        'account_type',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function ponds(): HasMany
    {
        return $this->hasMany(Pond::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function isHome(): bool
    {
        return $this->account_type === self::TYPE_HOME;
    }

    public function isFarm(): bool
    {
        return $this->account_type === self::TYPE_FARM;
    }

    public function unitsLabel(): string
    {
        return $this->isHome() ? 'Peceras' : 'Estanques';
    }

    public function unitLabel(): string
    {
        return $this->isHome() ? 'Pecera' : 'Estanque';
    }

    public function assignDefaultSubscription(): Subscription
    {
        $plan = Plan::query()
            ->where('code', $this->isHome() ? Plan::CODE_HOME : Plan::CODE_FARM)
            ->where('is_active', true)
            ->firstOrFail();

        return $this->subscription()->create([
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);
    }
}
