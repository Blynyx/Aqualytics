<?php

namespace Database\Seeders;

use App\Models\FishFarm;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class E2ETestSeeder extends Seeder
{
    /**
     * Controlled E2E data for Cypress and Dusk. Do not use in production.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            FishFarm::where('name', 'Piscigranja Cypress')
                ->get()
                ->each
                ->delete();

            User::whereIn('email', [
                'admin@cypress.test',
                'supervisor@cypress.test',
                'specialist@cypress.test',
            ])->get()->each->delete();

            $fishFarm = FishFarm::create([
                'name' => 'Piscigranja Cypress',
                'status' => 'active',
                'account_type' => FishFarm::TYPE_FARM,
            ]);
            $fishFarm->assignDefaultSubscription();

            $admin = $fishFarm->users()->create([
                'name' => 'Administrador Cypress',
                'email' => 'admin@cypress.test',
                'password' => 'password123',
                'role' => User::ROLE_ADMIN,
            ]);

            $fishFarm->users()->create([
                'name' => 'Supervisor Cypress',
                'email' => 'supervisor@cypress.test',
                'password' => 'password123',
                'role' => User::ROLE_SUPERVISOR,
            ]);

            $fishFarm->users()->create([
                'name' => 'Especialista Cypress',
                'email' => 'specialist@cypress.test',
                'password' => 'password123',
                'role' => User::ROLE_SPECIALIST,
            ]);

            $pond = $fishFarm->ponds()->create([
                'user_id' => $admin->id,
                'name' => 'Estanque Cypress',
                'code' => 'CYP-001',
                'species' => 'Tilapia',
                'location' => 'Zona de pruebas',
                'status' => 'active',
                'unit_type' => \App\Models\Pond::TYPE_POND,
            ]);

            $pond->threshold()->create([
                'ph_min' => 6.5,
                'ph_max' => 9,
            ]);

            $device = $pond->devices()->create([
                'name' => 'ESP32 Cypress',
                'device_uid' => 'CYP-ESP32-001',
                'status' => 'active',
            ]);

            foreach ([
                [now()->subHours(6), 24.8, 7.1, 28.0, 80],
                [now()->subHours(4), 25.1, 7.0, 30.2, 81],
                [now()->subHours(2), 25.4, 6.8, 32.0, 81],
            ] as [$recordedAt, $temperature, $ph, $turbidity, $waterLevel]) {
                $device->readings()->create([
                    'pond_id' => $pond->id,
                    'temperature' => $temperature,
                    'ph' => $ph,
                    'turbidity' => $turbidity,
                    'water_level' => $waterLevel,
                    'recorded_at' => $recordedAt,
                ]);
            }

            $reading = $device->readings()->create([
                'pond_id' => $pond->id,
                'temperature' => 25.6,
                'ph' => 5.8,
                'turbidity' => 34.5,
                'water_level' => 82,
                'recorded_at' => now(),
            ]);

            $reading->alerts()->create([
                'pond_id' => $pond->id,
                'device_id' => $device->id,
                'parameter' => 'ph',
                'value' => 5.8,
                'min_threshold' => 6.5,
                'severity' => 'warning',
                'status' => 'active',
                'message' => 'pH por debajo del rango configurado',
                'detected_at' => now(),
            ]);
        });
    }
}
