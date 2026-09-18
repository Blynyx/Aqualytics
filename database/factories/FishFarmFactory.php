<?php

namespace Database\Factories;

use App\Models\FishFarm;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\FishFarm>
 */
class FishFarmFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Piscigranja '.fake()->unique()->company(),
            'status' => 'active',
            'account_type' => FishFarm::TYPE_FARM,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (FishFarm $fishFarm): void {
            if ($fishFarm->subscription()->exists()) {
                return;
            }

            $planCode = $fishFarm->isHome() ? Plan::CODE_HOME : Plan::CODE_FARM;
            $plan = Plan::query()->where('code', $planCode)->first();

            if ($plan === null) {
                return;
            }

            $fishFarm->subscription()->create([
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
            ]);
        });
    }
}
