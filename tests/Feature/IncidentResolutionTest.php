<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\FishFarm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_specialist_can_resolve_assigned_alert_with_resolution_notes(): void
    {
        $fishFarm = FishFarm::factory()->create();
        $specialist = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $alert = $this->createAlert(
            $fishFarm,
            $specialist,
            'assigned',
            $specialist,
        );
        $notes = 'Se realizó recambio parcial del agua y nueva verificación del pH.';

        $response = $this
            ->actingAs($specialist)
            ->post("/alerts/{$alert->id}/resolve", [
                'resolution_notes' => $notes,
            ]);

        $response->assertRedirect("/ponds/{$alert->pond_id}");
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'resolved',
            'resolved_by_user_id' => $specialist->id,
            'resolution_notes' => $notes,
        ]);
        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    public function test_specialist_cannot_resolve_alert_assigned_to_another_specialist(): void
    {
        $fishFarm = FishFarm::factory()->create();
        $firstSpecialist = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $secondSpecialist = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $alert = $this->createAlert(
            $fishFarm,
            $secondSpecialist,
            'assigned',
            $secondSpecialist,
        );

        $this->actingAs($firstSpecialist)
            ->post("/alerts/{$alert->id}/resolve", [
                'resolution_notes' => 'Se verificó el estanque y se aplicó una corrección.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'assigned',
            'assigned_to_user_id' => $secondSpecialist->id,
        ]);
    }

    public function test_resolution_notes_are_required_for_specialist(): void
    {
        $fishFarm = FishFarm::factory()->create();
        $specialist = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $alert = $this->createAlert(
            $fishFarm,
            $specialist,
            'assigned',
            $specialist,
        );

        $response = $this
            ->actingAs($specialist)
            ->post("/alerts/{$alert->id}/resolve");

        $response->assertSessionHasErrors('resolution_notes');
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'assigned',
        ]);
    }

    public function test_admin_can_resolve_alert_with_resolution_notes(): void
    {
        $fishFarm = FishFarm::factory()->create();
        $admin = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_ADMIN,
        ]);
        $alert = $this->createAlert($fishFarm, $admin);
        $notes = 'El administrador realizó el ajuste y verificó los parámetros.';

        $this->actingAs($admin)
            ->post("/alerts/{$alert->id}/resolve", [
                'resolution_notes' => $notes,
            ])
            ->assertRedirect("/ponds/{$alert->pond_id}");

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'resolved',
            'resolved_by_user_id' => $admin->id,
            'resolution_notes' => $notes,
        ]);
        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    public function test_user_cannot_resolve_alert_from_another_fish_farm(): void
    {
        $firstFishFarm = FishFarm::factory()->create();
        $secondFishFarm = FishFarm::factory()->create();
        $specialist = User::factory()->create([
            'fish_farm_id' => $firstFishFarm->id,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $otherFarmSpecialist = User::factory()->create([
            'fish_farm_id' => $secondFishFarm->id,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $alert = $this->createAlert(
            $secondFishFarm,
            $otherFarmSpecialist,
            'assigned',
            $otherFarmSpecialist,
        );

        $this->actingAs($specialist)
            ->post("/alerts/{$alert->id}/resolve", [
                'resolution_notes' => 'No debe poder registrar esta resolución externa.',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'assigned',
        ]);
    }

    private function createAlert(
        FishFarm $fishFarm,
        User $creator,
        string $status = 'active',
        ?User $assignedTo = null,
    ): Alert {
        $pond = $fishFarm->ponds()->create([
            'user_id' => $creator->id,
            'name' => 'Estanque con incidencia',
            'code' => 'EST-RES-'.$creator->id,
        ]);
        $device = $pond->devices()->create([
            'name' => 'ESP32 de incidencia',
            'device_uid' => 'ESP32-RES-'.$creator->id,
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
            'status' => $status,
            'message' => 'pH por debajo del rango configurado',
            'detected_at' => now(),
            'assigned_to_user_id' => $assignedTo?->id,
            'assigned_at' => $assignedTo === null ? null : now(),
        ]);
    }
}
