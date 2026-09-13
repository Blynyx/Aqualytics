<?php

namespace Database\Factories;

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
        ];
    }
}
