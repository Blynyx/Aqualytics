<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\FishFarm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_assign_active_alert_to_specialist_from_same_fish_farm(): void
    {
        $fishFarm = FishFarm::factory()->create();
        $supervisor = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_SUPERVISOR,
        ]);
        $specialist = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $alert = $this->createAlert($fishFarm, $supervisor);

        $response = $this
            ->actingAs($supervisor)
            ->post("/alerts/{$alert->id}/assign", [
                'specialist_id' => $specialist->id,
            ]);

        $response->assertRedirect("/ponds/{$alert->pond_id}");
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'assigned',
            'assigned_to_user_id' => $specialist->id,
            'reported_by_user_id' => $supervisor->id,
        ]);
        $this->assertNotNull($alert->fresh()->assigned_at);
    }

    public function test_admin_can_assign_alert_to_specialist(): void
    {
        $fishFarm = FishFarm::factory()->create();
        $admin = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_ADMIN,
        ]);
        $specialist = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $alert = $this->createAlert($fishFarm, $admin);

        $response = $this
            ->actingAs($admin)
            ->post("/alerts/{$alert->id}/assign", [
                'specialist_id' => $specialist->id,
            ]);

        $response->assertRedirect("/ponds/{$alert->pond_id}");
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'assigned',
            'assigned_to_user_id' => $specialist->id,
            'reported_by_user_id' => $admin->id,
        ]);
        $this->assertNotNull($alert->fresh()->assigned_at);
    }

    public function test_specialist_cannot_assign_alert(): void
    {
        $fishFarm = FishFarm::factory()->create();
        $specialist = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $otherSpecialist = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $alert = $this->createAlert($fishFarm, $specialist);

        $this->actingAs($specialist)
            ->post("/alerts/{$alert->id}/assign", [
                'specialist_id' => $otherSpecialist->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'active',
        ]);
    }

    public function test_supervisor_cannot_assign_specialist_from_another_fish_farm(): void
    {
        $firstFishFarm = FishFarm::factory()->create();
        $secondFishFarm = FishFarm::factory()->create();
        $supervisor = User::factory()->create([
            'fish_farm_id' => $firstFishFarm->id,
            'role' => User::ROLE_SUPERVISOR,
        ]);
        $otherFarmSpecialist = User::factory()->create([
            'fish_farm_id' => $secondFishFarm->id,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $alert = $this->createAlert($firstFishFarm, $supervisor);

        $response = $this
            ->actingAs($supervisor)
            ->post("/alerts/{$alert->id}/assign", [
                'specialist_id' => $otherFarmSpecialist->id,
            ]);

        $response->assertSessionHasErrors('specialist_id');
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'active',
        ]);
    }

    public function test_user_cannot_assign_alert_from_another_fish_farm(): void
    {
        $firstFishFarm = FishFarm::factory()->create();
        $secondFishFarm = FishFarm::factory()->create();
        $supervisor = User::factory()->create([
            'fish_farm_id' => $firstFishFarm->id,
            'role' => User::ROLE_SUPERVISOR,
        ]);
        $specialist = User::factory()->create([
            'fish_farm_id' => $firstFishFarm->id,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $otherFarmAdmin = User::factory()->create([
            'fish_farm_id' => $secondFishFarm->id,
            'role' => User::ROLE_ADMIN,
        ]);
        $alert = $this->createAlert($secondFishFarm, $otherFarmAdmin);

        $this->actingAs($supervisor)
            ->post("/alerts/{$alert->id}/assign", [
                'specialist_id' => $specialist->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'active',
        ]);
    }

    private function createAlert(FishFarm $fishFarm, User $creator): Alert
    {
        $pond = $fishFarm->ponds()->create([
            'user_id' => $creator->id,
            'name' => 'Estanque con incidencia',
            'code' => 'EST-INC-'.$creator->id,
        ]);
        $device = $pond->devices()->create([
            'name' => 'ESP32 de incidencia',
            'device_uid' => 'ESP32-INC-'.$creator->id,
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
}
