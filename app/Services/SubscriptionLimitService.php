<?php

namespace App\Services;

use App\Models\Device;
use App\Models\FishFarm;
use App\Models\Plan;

class SubscriptionLimitService
{
    public function planFor(FishFarm $fishFarm): ?Plan
    {
        return $fishFarm->subscription?->plan;
    }

    public function canCreateUnit(FishFarm $fishFarm): bool
    {
        $plan = $this->planFor($fishFarm);

        return $plan !== null && $fishFarm->ponds()->count() < $plan->max_units;
    }

    public function canCreateUser(FishFarm $fishFarm): bool
    {
        $plan = $this->planFor($fishFarm);

        return $plan !== null && $fishFarm->users()->count() < $plan->max_users;
    }

    public function canCreateDevice(FishFarm $fishFarm): bool
    {
        $plan = $this->planFor($fishFarm);

        if ($plan === null) {
            return false;
        }

        $deviceCount = Device::query()
            ->whereIn('pond_id', $fishFarm->ponds()->select('id'))
            ->count();

        return $deviceCount < $plan->max_devices;
    }

    /**
     * @return array{units: array{current: int, max: int}, users: array{current: int, max: int}, devices: array{current: int, max: int}}
     */
    public function usage(FishFarm $fishFarm): array
    {
        $plan = $this->planFor($fishFarm);

        return [
            'units' => [
                'current' => $fishFarm->ponds()->count(),
                'max' => $plan?->max_units ?? 0,
            ],
            'users' => [
                'current' => $fishFarm->users()->count(),
                'max' => $plan?->max_users ?? 0,
            ],
            'devices' => [
                'current' => Device::query()
                    ->whereIn('pond_id', $fishFarm->ponds()->select('id'))
                    ->count(),
                'max' => $plan?->max_devices ?? 0,
            ],
        ];
    }
}
