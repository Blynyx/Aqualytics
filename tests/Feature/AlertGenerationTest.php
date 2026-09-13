<?php

namespace Tests\Feature;

use App\Models\Reading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_is_created_when_ph_is_below_configured_minimum(): void
    {
        [$pond, $device] = $this->createDevice([
            'ph_min' => 6.5,
            'ph_max' => 9,
        ]);

        $response = $this->postJson('/api/readings', $this->readingPayload([
            'ph' => 5.8,
        ]));

        $response->assertStatus(201);

        $reading = Reading::where('device_id', $device->id)->firstOrFail();

        $this->assertDatabaseHas('readings', [
            'id' => $reading->id,
            'ph' => 5.8,
        ]);
        $this->assertDatabaseHas('alerts', [
            'reading_id' => $reading->id,
            'pond_id' => $pond->id,
            'device_id' => $device->id,
            'parameter' => 'ph',
            'value' => 5.8,
            'min_threshold' => 6.5,
            'severity' => 'warning',
            'status' => 'active',
        ]);
    }

    public function test_alert_is_created_when_temperature_is_above_configured_maximum(): void
    {
        [$pond, $device] = $this->createDevice([
            'temperature_max' => 32,
        ]);

        $response = $this->postJson('/api/readings', $this->readingPayload([
            'temperature' => 35,
        ]));

        $response->assertStatus(201);

        $reading = Reading::where('device_id', $device->id)->firstOrFail();

        $this->assertDatabaseHas('alerts', [
            'reading_id' => $reading->id,
            'pond_id' => $pond->id,
            'device_id' => $device->id,
            'parameter' => 'temperature',
            'value' => 35,
            'max_threshold' => 32,
            'status' => 'active',
        ]);
    }

    public function test_alert_is_created_when_turbidity_exceeds_maximum(): void
    {
        [$pond, $device] = $this->createDevice([
            'turbidity_max' => 100,
        ]);

        $response = $this->postJson('/api/readings', $this->readingPayload([
            'turbidity' => 120,
        ]));

        $response->assertStatus(201);

        $reading = Reading::where('device_id', $device->id)->firstOrFail();

        $this->assertDatabaseHas('alerts', [
            'reading_id' => $reading->id,
            'pond_id' => $pond->id,
            'device_id' => $device->id,
            'parameter' => 'turbidity',
            'value' => 120,
            'max_threshold' => 100,
            'status' => 'active',
        ]);
    }

    public function test_no_alert_is_created_when_values_are_inside_configured_ranges(): void
    {
        [, $device] = $this->createDevice([
            'temperature_min' => 20,
            'temperature_max' => 32,
            'ph_min' => 6.5,
            'ph_max' => 9,
            'turbidity_max' => 100,
            'water_level_min' => 50,
            'water_level_max' => 100,
        ]);

        $response = $this->postJson('/api/readings', $this->readingPayload());

        $response->assertStatus(201);
        $this->assertDatabaseHas('readings', [
            'device_id' => $device->id,
        ]);
        $this->assertDatabaseCount('alerts', 0);
    }

    public function test_reading_is_stored_even_when_it_generates_alert(): void
    {
        [$pond, $device] = $this->createDevice([
            'ph_min' => 6.5,
            'ph_max' => 9,
        ]);

        $response = $this->postJson('/api/readings', $this->readingPayload([
            'ph' => 5.8,
        ]));

        $response->assertStatus(201);
        $this->assertDatabaseHas('readings', [
            'device_id' => $device->id,
            'pond_id' => $pond->id,
            'ph' => 5.8,
        ]);
        $this->assertDatabaseHas('alerts', [
            'pond_id' => $pond->id,
            'device_id' => $device->id,
            'parameter' => 'ph',
        ]);
    }

    public function test_no_alert_rule_is_applied_when_pond_has_no_threshold_configuration(): void
    {
        [, $device] = $this->createDevice();

        $response = $this->postJson('/api/readings', $this->readingPayload());

        $response->assertStatus(201);
        $this->assertDatabaseHas('readings', [
            'device_id' => $device->id,
        ]);
        $this->assertDatabaseCount('alerts', 0);
    }

    private function createDevice(?array $thresholds = null): array
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

        if ($thresholds !== null) {
            $pond->threshold()->create($thresholds);
        }

        return [$pond, $device];
    }

    private function readingPayload(array $overrides = []): array
    {
        return array_merge([
            'device_uid' => 'ESP32-001',
            'temperature' => 25.6,
            'ph' => 7.2,
            'turbidity' => 34.5,
            'water_level' => 82.0,
        ], $overrides);
    }
}
