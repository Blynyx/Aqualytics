<?php

namespace Database\Seeders;

use App\Models\FishFarm;
use App\Models\Incident;
use App\Models\InternalNotification;
use App\Models\Pond;
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

            FishFarm::where('name', 'Cuenta Home Cypress')
                ->get()
                ->each
                ->delete();

            User::whereIn('email', [
                'admin@cypress.test',
                'supervisor@cypress.test',
                'specialist@cypress.test',
                'specialist-b@cypress.test',
                'home@cypress.test',
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

            $specialist = $fishFarm->users()->create([
                'name' => 'Especialista Cypress',
                'email' => 'specialist@cypress.test',
                'password' => 'password123',
                'role' => User::ROLE_SPECIALIST,
            ]);

            $specialistB = $fishFarm->users()->create([
                'name' => 'Especialista Cypress B',
                'email' => 'specialist-b@cypress.test',
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
                'unit_type' => Pond::TYPE_POND,
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

            $alert = $reading->alerts()->create([
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

            $fishFarm->users()
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR])
                ->get()
                ->each(function (User $recipient) use ($fishFarm, $alert): void {
                    InternalNotification::query()->create([
                        'fish_farm_id' => $fishFarm->id,
                        'user_id' => $recipient->id,
                        'type' => InternalNotification::TYPE_ALERT_CREATED,
                        'source_type' => InternalNotification::SOURCE_ALERT,
                        'source_id' => $alert->id,
                        'title' => 'Nueva alerta hídrica',
                        'message' => 'pH por debajo del rango configurado en Estanque Cypress.',
                    ]);
                });

            $workPond = $fishFarm->ponds()->create([
                'user_id' => $admin->id,
                'name' => 'Estanque Especialista',
                'code' => 'CYP-002',
                'species' => 'Tilapia',
                'location' => 'Zona de especialistas',
                'status' => 'active',
                'unit_type' => Pond::TYPE_POND,
            ]);

            $workDevice = $workPond->devices()->create([
                'name' => 'ESP32 Especialista',
                'device_uid' => 'CYP-ESP32-002',
                'status' => 'active',
            ]);

            $this->seedAssignedIncident(
                $fishFarm,
                $admin,
                $workPond,
                $workDevice,
                $specialist,
                'Incidencia del especialista A',
                5.7,
            );
            $this->seedAssignedIncident(
                $fishFarm,
                $admin,
                $workPond,
                $workDevice,
                $specialistB,
                'Incidencia del especialista B',
                5.6,
            );

            $home = FishFarm::create([
                'name' => 'Cuenta Home Cypress',
                'status' => 'active',
                'account_type' => FishFarm::TYPE_HOME,
            ]);
            $home->assignDefaultSubscription();

            $homeOwner = $home->users()->create([
                'name' => 'Propietario Home Cypress',
                'email' => 'home@cypress.test',
                'password' => 'password123',
                'role' => User::ROLE_ADMIN,
            ]);

            $aquarium = $home->ponds()->create([
                'user_id' => $homeOwner->id,
                'name' => 'Pecera Cypress',
                'code' => 'HOM-001',
                'species' => 'Guppy',
                'location' => 'Sala',
                'status' => 'active',
                'unit_type' => Pond::TYPE_AQUARIUM,
            ]);

            $aquarium->threshold()->create([
                'ph_min' => 6.5,
                'ph_max' => 8,
                'temperature_min' => 22,
                'temperature_max' => 28,
            ]);

            $homeDevice = $aquarium->devices()->create([
                'name' => 'ESP32 Home',
                'device_uid' => 'HOM-ESP32-001',
                'status' => 'active',
            ]);

            $homeDevice->readings()->create([
                'pond_id' => $aquarium->id,
                'temperature' => 24.5,
                'ph' => 7.2,
                'turbidity' => 12.0,
                'water_level' => 90,
                'recorded_at' => now(),
            ]);
        });
    }

    private function seedAssignedIncident(
        FishFarm $fishFarm,
        User $creator,
        Pond $pond,
        $device,
        User $assignee,
        string $title,
        float $ph,
    ): void {
        $reading = $device->readings()->create([
            'pond_id' => $pond->id,
            'temperature' => 25.2,
            'ph' => $ph,
            'turbidity' => 30.0,
            'water_level' => 80,
            'recorded_at' => now(),
        ]);

        $alert = $reading->alerts()->create([
            'pond_id' => $pond->id,
            'device_id' => $device->id,
            'parameter' => 'ph',
            'value' => $ph,
            'min_threshold' => 6.5,
            'severity' => 'warning',
            'status' => 'assigned',
            'message' => 'Alerta de '.$title,
            'detected_at' => now(),
        ]);

        Incident::query()->create([
            'alert_id' => $alert->id,
            'fish_farm_id' => $fishFarm->id,
            'created_by' => $creator->id,
            'assigned_to' => $assignee->id,
            'title' => $title,
            'description' => 'Trabajo asignado para Cypress.',
            'status' => Incident::STATUS_ASSIGNED,
        ]);
    }
}
