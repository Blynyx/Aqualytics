<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_resolve_alert_from_own_pond(): void
    {
        $user = User::factory()->create();
        [$pond, $alert] = $this->createAlertFor($user);

        $response = $this
            ->actingAs($user)
            ->post("/alerts/{$alert->id}/resolve", [
                'resolution_notes' => 'El administrador verificó y corrigió la incidencia.',
            ]);

        $response->assertRedirect("/ponds/{$pond->id}");
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'resolved',
            'resolved_by_user_id' => $user->id,
            'resolution_notes' => 'El administrador verificó y corrigió la incidencia.',
        ]);
        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    public function test_user_cannot_resolve_alert_from_another_fish_farms_pond(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        [, $alert] = $this->createAlertFor($secondUser);

        $response = $this
            ->actingAs($firstUser)
            ->post("/alerts/{$alert->id}/resolve");

        $response->assertNotFound();
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'active',
            'resolved_at' => null,
        ]);
    }

    private function createAlertFor(User $user): array
    {
        $pond = $user->ponds()->create([
            'name' => 'Estanque Principal',
            'code' => 'EST-'.$user->id,
        ]);
        $device = $pond->devices()->create([
            'name' => 'ESP32 Principal',
            'device_uid' => 'ESP32-'.$user->id,
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

        return [$pond, $alert];
    }
}
