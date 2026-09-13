<?php

namespace Tests\Feature;

use App\Models\FishFarm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_roles_can_view_dashboard(): void
    {
        $fishFarm = FishFarm::factory()->create();

        foreach ($this->roles() as $role) {
            $user = User::factory()->create([
                'fish_farm_id' => $fishFarm->id,
                'role' => $role,
            ]);

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk();
        }
    }

    public function test_all_roles_can_view_pond_from_their_fish_farm(): void
    {
        $fishFarm = FishFarm::factory()->create();
        $admin = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_ADMIN,
        ]);
        $pond = $fishFarm->ponds()->create([
            'user_id' => $admin->id,
            'name' => 'Estanque Compartido por Roles',
            'code' => 'EST-ROLES',
        ]);

        foreach ($this->roles() as $role) {
            $user = User::factory()->create([
                'fish_farm_id' => $fishFarm->id,
                'role' => $role,
            ]);

            $this->actingAs($user)
                ->get("/ponds/{$pond->id}")
                ->assertOk();
        }
    }

    public function test_supervisor_cannot_create_pond(): void
    {
        $supervisor = User::factory()->create([
            'role' => User::ROLE_SUPERVISOR,
        ]);

        $this->actingAs($supervisor)
            ->get('/ponds/create')
            ->assertForbidden();

        $this->actingAs($supervisor)
            ->post('/ponds', [
                'name' => 'Estanque no autorizado',
                'code' => 'EST-NO-SUP',
            ])
            ->assertForbidden();
    }

    public function test_specialist_cannot_create_pond(): void
    {
        $specialist = User::factory()->create([
            'role' => User::ROLE_SPECIALIST,
        ]);

        $this->actingAs($specialist)
            ->get('/ponds/create')
            ->assertForbidden();

        $this->actingAs($specialist)
            ->post('/ponds', [
                'name' => 'Estanque no autorizado',
                'code' => 'EST-NO-ESP',
            ])
            ->assertForbidden();
    }

    public function test_supervisor_cannot_register_device(): void
    {
        [$supervisor, $pond] = $this->createUserAndPond(User::ROLE_SUPERVISOR);

        $this->actingAs($supervisor)
            ->post("/ponds/{$pond->id}/devices", [
                'name' => 'ESP32 no autorizado',
                'device_uid' => 'ESP32-NO-SUP',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('devices', [
            'device_uid' => 'ESP32-NO-SUP',
        ]);
    }

    public function test_specialist_cannot_register_device(): void
    {
        [$specialist, $pond] = $this->createUserAndPond(User::ROLE_SPECIALIST);

        $this->actingAs($specialist)
            ->post("/ponds/{$pond->id}/devices", [
                'name' => 'ESP32 no autorizado',
                'device_uid' => 'ESP32-NO-ESP',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('devices', [
            'device_uid' => 'ESP32-NO-ESP',
        ]);
    }

    public function test_supervisor_cannot_configure_thresholds(): void
    {
        [$supervisor, $pond] = $this->createUserAndPond(User::ROLE_SUPERVISOR);

        $this->actingAs($supervisor)
            ->post("/ponds/{$pond->id}/thresholds", [
                'ph_min' => 6.5,
                'ph_max' => 9,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('pond_thresholds', [
            'pond_id' => $pond->id,
        ]);
    }

    public function test_specialist_cannot_configure_thresholds(): void
    {
        [$specialist, $pond] = $this->createUserAndPond(User::ROLE_SPECIALIST);

        $this->actingAs($specialist)
            ->post("/ponds/{$pond->id}/thresholds", [
                'ph_min' => 6.5,
                'ph_max' => 9,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('pond_thresholds', [
            'pond_id' => $pond->id,
        ]);
    }

    public function test_supervisor_cannot_resolve_alert(): void
    {
        $fishFarm = FishFarm::factory()->create();
        $supervisor = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_SUPERVISOR,
        ]);
        $alert = $this->createAlert($fishFarm, $supervisor);

        $this->actingAs($supervisor)
            ->post("/alerts/{$alert->id}/resolve")
            ->assertForbidden();

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'active',
            'resolved_at' => null,
        ]);
    }

    public function test_specialist_can_resolve_alert_from_own_fish_farm(): void
    {
        $fishFarm = FishFarm::factory()->create();
        $specialist = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $alert = $this->createAlert($fishFarm, $specialist);
        $alert->update([
            'assigned_to_user_id' => $specialist->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        $this->actingAs($specialist)
            ->post("/alerts/{$alert->id}/resolve", [
                'resolution_notes' => 'El especialista aplicó la acción correctiva requerida.',
            ])
            ->assertRedirect("/ponds/{$alert->pond_id}");

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'resolved',
            'resolved_by_user_id' => $specialist->id,
        ]);
        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    public function test_specialist_cannot_resolve_alert_from_another_fish_farm(): void
    {
        $firstFishFarm = FishFarm::factory()->create();
        $secondFishFarm = FishFarm::factory()->create();
        $specialist = User::factory()->create([
            'fish_farm_id' => $firstFishFarm->id,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $secondFarmUser = User::factory()->create([
            'fish_farm_id' => $secondFishFarm->id,
            'role' => User::ROLE_ADMIN,
        ]);
        $alert = $this->createAlert($secondFishFarm, $secondFarmUser);

        $this->actingAs($specialist)
            ->post("/alerts/{$alert->id}/resolve")
            ->assertNotFound();

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'active',
            'resolved_at' => null,
        ]);
    }

    private function createUserAndPond(string $role): array
    {
        $user = User::factory()->create([
            'role' => $role,
        ]);
        $pond = $user->fishFarm->ponds()->create([
            'user_id' => $user->id,
            'name' => 'Estanque para autorización',
            'code' => 'EST-'.$user->id,
        ]);

        return [$user, $pond];
    }

    private function createAlert(FishFarm $fishFarm, User $creator)
    {
        $pond = $fishFarm->ponds()->create([
            'user_id' => $creator->id,
            'name' => 'Estanque con alerta',
            'code' => 'EST-ALERTA-'.$creator->id,
        ]);
        $device = $pond->devices()->create([
            'name' => 'ESP32 de alerta',
            'device_uid' => 'ESP32-ALERTA-'.$creator->id,
            'status' => 'active',
        ]);
        $reading = $device->readings()->create([
            'pond_id' => $pond->id,
            'temperature' => 25.6,
            'ph' => 5.8,
            'turbidity' => 34.5,
            'water_level' => 82,
            'recorded_at' => now(),
        ]);

        return $reading->alerts()->create([
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
    }

    private function roles(): array
    {
        return [
            User::ROLE_ADMIN,
            User::ROLE_SUPERVISOR,
            User::ROLE_SPECIALIST,
        ];
    }
}
