<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Device;
use App\Models\FishFarm;
use App\Models\Pond;
use App\Models\Reading;
use App\Models\User;
use App\Services\AlertEvaluationService;
use App\Services\ReadingIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IoTSimulatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulator_creates_requested_number_of_readings(): void
    {
        [, $device] = $this->createDeviceWithThresholds();

        $this->artisan('aqualytics:simulate', [
            '--device' => $device->device_uid,
            '--count' => 5,
            '--interval' => 0,
            '--mode' => 'normal',
        ])->assertSuccessful();

        $this->assertSame(5, Reading::query()->where('device_id', $device->id)->count());
    }

    public function test_simulator_updates_device_last_seen(): void
    {
        [, $device] = $this->createDeviceWithThresholds();

        $this->assertNull($device->last_seen_at);

        $this->artisan('aqualytics:simulate', [
            '--device' => $device->device_uid,
            '--count' => 3,
            '--interval' => 0,
            '--mode' => 'normal',
        ])->assertSuccessful();

        $this->assertNotNull($device->fresh()->last_seen_at);
    }

    public function test_normal_mode_generates_values_inside_thresholds(): void
    {
        [, $device] = $this->createDeviceWithThresholds();

        $this->artisan('aqualytics:simulate', [
            '--device' => $device->device_uid,
            '--count' => 12,
            '--interval' => 0,
            '--mode' => 'normal',
        ])->assertSuccessful();

        $readings = Reading::query()->where('device_id', $device->id)->get();

        $this->assertCount(12, $readings);
        $this->assertDatabaseCount('alerts', 0);

        foreach ($readings as $reading) {
            $this->assertTrue($reading->temperature >= 20 && $reading->temperature <= 32);
            $this->assertTrue($reading->ph >= 6.5 && $reading->ph <= 9);
            $this->assertTrue($reading->turbidity >= 0 && $reading->turbidity <= 100);
            $this->assertTrue($reading->water_level >= 50 && $reading->water_level <= 100);
        }
    }

    public function test_anomaly_mode_generates_out_of_range_value(): void
    {
        [, $device] = $this->createDeviceWithThresholds();

        $this->artisan('aqualytics:simulate', [
            '--device' => $device->device_uid,
            '--count' => 8,
            '--interval' => 0,
            '--mode' => 'anomaly',
        ])->assertSuccessful();

        $readings = Reading::query()->where('device_id', $device->id)->get();

        $this->assertTrue(
            $readings->contains(fn (Reading $reading): bool => $this->readingIsOutOfRange($reading)),
            'Expected at least one simulated reading outside the configured thresholds.',
        );
    }

    public function test_anomaly_generates_alert_through_ingestion_service(): void
    {
        [, $device] = $this->createDeviceWithThresholds();

        $this->artisan('aqualytics:simulate', [
            '--device' => $device->device_uid,
            '--count' => 6,
            '--interval' => 0,
            '--mode' => 'anomaly',
        ])->assertSuccessful();

        $alerts = Alert::query()->where('device_id', $device->id)->get();

        $this->assertGreaterThan(0, $alerts->count());
        $this->assertTrue($alerts->every(fn (Alert $alert): bool => $alert->reading_id !== null));
        $this->assertTrue(
            Reading::query()->whereIn('id', $alerts->pluck('reading_id'))->exists(),
            'Alerts must be created from ingested readings, not by the command.',
        );
    }

    public function test_invalid_device_returns_failure(): void
    {
        $this->artisan('aqualytics:simulate', [
            '--device' => 'ESP32-NO-EXISTE',
            '--count' => 1,
            '--interval' => 0,
        ])->assertFailed();

        $this->assertDatabaseCount('readings', 0);
        $this->assertDatabaseCount('devices', 0);
    }

    public function test_home_device_can_be_simulated(): void
    {
        $account = FishFarm::factory()->create([
            'account_type' => FishFarm::TYPE_HOME,
        ]);
        $admin = User::factory()->create([
            'fish_farm_id' => $account->id,
        ]);
        $pond = $account->ponds()->create([
            'user_id' => $admin->id,
            'name' => 'Pecera Principal',
            'code' => 'PEC-HOME',
            'unit_type' => Pond::TYPE_AQUARIUM,
        ]);
        $device = $this->attachDevice($pond, 'HOME-ESP32-001');

        $this->artisan('aqualytics:simulate', [
            '--device' => 'HOME-ESP32-001',
            '--count' => 4,
            '--interval' => 0,
            '--mode' => 'normal',
        ])->assertSuccessful();

        $this->assertSame(4, Reading::query()->where('device_id', $device->id)->count());
        $this->assertNotNull($device->fresh()->last_seen_at);
    }

    public function test_farm_device_can_be_simulated(): void
    {
        $account = FishFarm::factory()->create([
            'account_type' => FishFarm::TYPE_FARM,
        ]);
        $admin = User::factory()->create([
            'fish_farm_id' => $account->id,
        ]);
        $pond = $account->ponds()->create([
            'user_id' => $admin->id,
            'name' => 'Estanque Principal',
            'code' => 'EST-FARM',
            'unit_type' => Pond::TYPE_POND,
        ]);
        $device = $this->attachDevice($pond, 'FARM-ESP32-001');

        $this->artisan('aqualytics:simulate', [
            '--device' => 'FARM-ESP32-001',
            '--count' => 4,
            '--interval' => 0,
            '--mode' => 'normal',
        ])->assertSuccessful();

        $this->assertSame(4, Reading::query()->where('device_id', $device->id)->count());
        $this->assertNotNull($device->fresh()->last_seen_at);
    }

    public function test_api_readings_still_use_same_ingestion_pipeline(): void
    {
        [, $device] = $this->createDeviceWithThresholds();
        $resolved = false;

        $this->app->bind(ReadingIngestionService::class, function ($app) use (&$resolved) {
            $resolved = true;

            return new ReadingIngestionService($app->make(AlertEvaluationService::class));
        });

        $token = $device->issueToken();

        $this->postJson('/api/readings', [
            'device_uid' => $device->device_uid,
            'temperature' => 25.6,
            'ph' => 5.8,
            'turbidity' => 34.5,
            'water_level' => 82.0,
        ], [
            'X-Device-Token' => $token,
        ])->assertCreated();

        $this->assertTrue($resolved);
        $this->assertDatabaseHas('readings', [
            'device_id' => $device->id,
            'pond_id' => $device->pond_id,
            'temperature' => 25.6,
            'ph' => 5.8,
            'turbidity' => 34.5,
            'water_level' => 82.0,
        ]);
        $this->assertDatabaseHas('alerts', [
            'device_id' => $device->id,
            'parameter' => 'ph',
            'value' => 5.8,
        ]);
        $this->assertNotNull($device->fresh()->last_seen_at);
    }

    /**
     * @return array{0: Pond, 1: Device}
     */
    private function createDeviceWithThresholds(
        string $deviceUid = 'ESP32-001',
        array $thresholds = [],
    ): array {
        $user = User::factory()->create();
        $pond = $user->ponds()->create([
            'name' => 'Estanque 01',
            'code' => 'EST-001',
        ]);
        $device = $this->attachDevice($pond, $deviceUid, $thresholds);

        return [$pond, $device];
    }

    private function attachDevice(Pond $pond, string $deviceUid, array $thresholds = []): Device
    {
        $device = $pond->devices()->create([
            'name' => 'ESP32 '.$pond->code,
            'device_uid' => $deviceUid,
            'status' => 'active',
        ]);

        $pond->threshold()->create(array_merge([
            'temperature_min' => 20,
            'temperature_max' => 32,
            'ph_min' => 6.5,
            'ph_max' => 9,
            'turbidity_max' => 100,
            'water_level_min' => 50,
            'water_level_max' => 100,
        ], $thresholds));

        return $device;
    }

    private function readingIsOutOfRange(Reading $reading): bool
    {
        return $reading->temperature < 20
            || $reading->temperature > 32
            || $reading->ph < 6.5
            || $reading->ph > 9
            || $reading->turbidity > 100
            || $reading->water_level < 50
            || $reading->water_level > 100;
    }
}
