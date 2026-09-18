<?php

namespace Database\Seeders;

use App\Models\FishFarm;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Optional local/demo data. Do not use in production or E2E.
     */
    public function run(): void
    {
        $this->seedFarmDemo();
        $this->seedHomeDemo();
    }

    private function seedFarmDemo(): void
    {
        $user = User::query()->where('email', 'test@example.com')->first()
            ?? User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $pond = $user->ponds()->firstOrCreate(
            ['code' => 'DEMO-FARM'],
            [
                'name' => 'Estanque Demo',
                'unit_type' => Pond::TYPE_POND,
                'status' => 'active',
                'fish_farm_id' => $user->fish_farm_id,
            ],
        );

        $this->ensureOwnedByAccount($pond, $user);
        $this->ensureThresholdsAndDevice($pond, 'DEMO-FARM-ESP32-001', 'ESP32 Demo Farm');
    }

    private function seedHomeDemo(): void
    {
        $user = User::query()->where('email', 'demo-home@example.com')->first();

        if ($user === null) {
            $account = FishFarm::factory()->create([
                'name' => 'Cuenta Demo Home',
                'account_type' => FishFarm::TYPE_HOME,
            ]);

            $user = User::factory()->create([
                'fish_farm_id' => $account->id,
                'name' => 'Demo Home',
                'email' => 'demo-home@example.com',
            ]);
        }

        $pond = $user->ponds()->firstOrCreate(
            ['code' => 'DEMO-HOME'],
            [
                'name' => 'Pecera Demo',
                'unit_type' => Pond::TYPE_AQUARIUM,
                'status' => 'active',
                'fish_farm_id' => $user->fish_farm_id,
            ],
        );

        $this->ensureOwnedByAccount($pond, $user);
        $this->ensureThresholdsAndDevice($pond, 'DEMO-HOME-ESP32-001', 'ESP32 Demo Home');
    }

    private function ensureOwnedByAccount(Pond $pond, User $user): void
    {
        if ($pond->fish_farm_id === $user->fish_farm_id) {
            return;
        }

        $pond->forceFill([
            'fish_farm_id' => $user->fish_farm_id,
        ])->save();
    }

    private function ensureThresholdsAndDevice(Pond $pond, string $deviceUid, string $deviceName): void
    {
        $pond->threshold()->firstOrCreate([], [
            'temperature_min' => 22,
            'temperature_max' => 28,
            'ph_min' => 6.5,
            'ph_max' => 8.0,
            'turbidity_max' => 50,
            'water_level_min' => 60,
            'water_level_max' => 90,
        ]);

        $pond->devices()->firstOrCreate(
            ['device_uid' => $deviceUid],
            [
                'name' => $deviceName,
                'status' => 'active',
            ],
        );
    }
}
