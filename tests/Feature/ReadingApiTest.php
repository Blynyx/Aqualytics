<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_can_submit_water_reading(): void
    {
        $user = User::factory()->create();
        $pond = $user->ponds()->create([
            'name' => 'Estanque 01',
            'code' => 'EST-001',
        ]);
        $device = $pond->devices()->create([
            'name' => 'ESP32 Estanque 01',
            'device_uid' => 'ESP32-001',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/readings', [
            'device_uid' => 'ESP32-001',
            'temperature' => 25.6,
            'ph' => 7.2,
            'turbidity' => 34.5,
            'water_level' => 82.0,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Lectura registrada correctamente',
        ]);

        $this->assertDatabaseHas('readings', [
            'device_id' => $device->id,
            'pond_id' => $pond->id,
            'temperature' => 25.6,
            'ph' => 7.2,
            'turbidity' => 34.5,
            'water_level' => 82.0,
        ]);

        $this->assertNotNull($device->fresh()->last_seen_at);
    }

    public function test_ph_greater_than_14_is_rejected(): void
    {
        $user = User::factory()->create();
        $pond = $user->ponds()->create([
            'name' => 'Estanque 01',
            'code' => 'EST-001',
        ]);
        $pond->devices()->create([
            'name' => 'ESP32 Estanque 01',
            'device_uid' => 'ESP32-001',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/readings', [
            'device_uid' => 'ESP32-001',
            'temperature' => 25,
            'ph' => 18,
            'turbidity' => 30,
            'water_level' => 80,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ph');
        $this->assertDatabaseCount('readings', 0);
    }

    public function test_temperature_is_required(): void
    {
        $user = User::factory()->create();
        $pond = $user->ponds()->create([
            'name' => 'Estanque 01',
            'code' => 'EST-001',
        ]);
        $pond->devices()->create([
            'name' => 'ESP32 Estanque 01',
            'device_uid' => 'ESP32-001',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/readings', [
            'device_uid' => 'ESP32-001',
            'ph' => 7.2,
            'turbidity' => 30,
            'water_level' => 80,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('temperature');
        $this->assertDatabaseCount('readings', 0);
    }

    public function test_unknown_device_is_rejected(): void
    {
        $response = $this->postJson('/api/readings', [
            'device_uid' => 'ESP32-NO-EXISTE',
            'temperature' => 25,
            'ph' => 7.2,
            'turbidity' => 30,
            'water_level' => 80,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('device_uid');
        $this->assertDatabaseCount('readings', 0);
    }

    public function test_negative_turbidity_and_water_level_are_rejected(): void
    {
        $user = User::factory()->create();
        $pond = $user->ponds()->create([
            'name' => 'Estanque 01',
            'code' => 'EST-001',
        ]);
        $pond->devices()->create([
            'name' => 'ESP32 Estanque 01',
            'device_uid' => 'ESP32-001',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/readings', [
            'device_uid' => 'ESP32-001',
            'temperature' => 25,
            'ph' => 7.2,
            'turbidity' => -1,
            'water_level' => -10,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'turbidity',
            'water_level',
        ]);
        $this->assertDatabaseCount('readings', 0);
    }
}
