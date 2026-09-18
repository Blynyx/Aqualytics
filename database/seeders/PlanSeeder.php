<?php

namespace Database\Seeders;

use App\Models\FishFarm;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::query()->updateOrCreate(
            ['code' => Plan::CODE_HOME],
            [
                'name' => 'Aqualytics Home',
                'account_type' => FishFarm::TYPE_HOME,
                'monthly_price' => '0.00',
                'max_units' => 1,
                'max_users' => 1,
                'max_devices' => 1,
                'history_days' => 7,
                'is_active' => true,
            ],
        );

        Plan::query()->updateOrCreate(
            ['code' => Plan::CODE_FARM],
            [
                'name' => 'Aqualytics Farm',
                'account_type' => FishFarm::TYPE_FARM,
                'monthly_price' => '0.00',
                'max_units' => 10,
                'max_users' => 10,
                'max_devices' => 10,
                'history_days' => 90,
                'is_active' => true,
            ],
        );
    }
}
