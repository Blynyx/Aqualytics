<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Device;
use App\Models\Reading;
use App\Models\User;
use App\Services\ReadingIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingIngestionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingest_creates_reading_and_updates_device(): void
    {
        $device = $this->createDevice();

        $result = app(ReadingIngestionService::class)->ingest($device, [
            'temperature' => 25.6,
            'ph' => 7.2,
            'turbidity' => 34.5,
            'water_level' => 82.0,
        ]);

        $this->assertInstanceOf(Reading::class, $result->reading);
        $this->assertTrue($result->alerts->isEmpty());
        $this->assertDatabaseHas('readings', [
            'id' => $result->reading->id,
            'device_id' => $device->id,
            'pond_id' => $device->pond_id,
            'temperature' => 25.6,
            'ph' => 7.2,
            'turbidity' => 34.5,
            'water_level' => 82.0,
        ]);
        $this->assertNotNull($device->fresh()->last_seen_at);
    }

    public function test_ingest_creates_alert_when_threshold_is_exceeded(): void
    {
        $device = $this->createDevice([
            'ph_min' => 6.5,
            'ph_max' => 9,
        ]);

        $result = app(ReadingIngestionService::class)->ingest($device, [
            'temperature' => 25.6,
            'ph' => 5.8,
            'turbidity' => 34.5,
            'water_level' => 82.0,
        ]);

        $this->assertFalse($result->alerts->isEmpty());
        $this->assertTrue($result->hasAnomaly());
        $this->assertDatabaseHas('alerts', [
            'reading_id' => $result->reading->id,
            'device_id' => $device->id,
            'parameter' => 'ph',
            'value' => 5.8,
            'min_threshold' => 6.5,
            'status' => 'active',
        ]);
        $this->assertSame(1, Alert::query()->where('reading_id', $result->reading->id)->count());
    }

    public function test_ingest_does_not_create_alerts_without_thresholds(): void
    {
        $device = $this->createDevice();

        $result = app(ReadingIngestionService::class)->ingest($device, [
            'temperature' => 25.6,
            'ph' => 5.8,
            'turbidity' => 34.5,
            'water_level' => 82.0,
        ]);

        $this->assertTrue($result->alerts->isEmpty());
        $this->assertDatabaseCount('alerts', 0);
        $this->assertDatabaseCount('readings', 1);
    }

    private function createDevice(?array $thresholds = null): Device
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

        return $device;
    }
}
