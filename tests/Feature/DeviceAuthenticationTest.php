<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_device_token_can_submit_reading(): void
    {
        [$device, $token] = $this->deviceWithToken();

        $this->postJson('/api/readings', $this->payload($device), [
            'X-Device-Token' => $token,
        ])->assertCreated();
    }

    public function test_missing_device_token_is_rejected(): void
    {
        [$device] = $this->deviceWithToken();

        $this->postJson('/api/readings', $this->payload($device))
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Dispositivo no autorizado.',
            ]);

        $this->assertDatabaseCount('readings', 0);
    }

    public function test_invalid_device_token_is_rejected(): void
    {
        [$device] = $this->deviceWithToken();

        $this->postJson('/api/readings', $this->payload($device), [
            'X-Device-Token' => 'token-incorrecto',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Dispositivo no autorizado.',
            ]);

        $this->assertDatabaseCount('readings', 0);
    }

    public function test_token_for_another_device_is_rejected(): void
    {
        [$device] = $this->deviceWithToken('ESP32-A');
        [, $otherToken] = $this->deviceWithToken('ESP32-B');

        $this->postJson('/api/readings', $this->payload($device), [
            'X-Device-Token' => $otherToken,
        ])->assertUnauthorized();

        $this->assertDatabaseCount('readings', 0);
    }

    public function test_valid_request_still_creates_reading(): void
    {
        [$device, $token] = $this->deviceWithToken();

        $this->postJson('/api/readings', $this->payload($device), [
            'X-Device-Token' => $token,
        ])->assertCreated();

        $this->assertDatabaseHas('readings', [
            'device_id' => $device->id,
            'pond_id' => $device->pond_id,
            'temperature' => 25.6,
            'ph' => 7.2,
            'turbidity' => 34.5,
            'water_level' => 82.0,
        ]);
    }

    public function test_valid_request_updates_last_seen_at(): void
    {
        [$device, $token] = $this->deviceWithToken();

        $this->assertNull($device->last_seen_at);

        $this->postJson('/api/readings', $this->payload($device), [
            'X-Device-Token' => $token,
        ])->assertCreated();

        $this->assertNotNull($device->fresh()->last_seen_at);
    }

    public function test_valid_request_still_generates_alerts(): void
    {
        [$device, $token] = $this->deviceWithToken();
        $device->pond->threshold()->create([
            'ph_min' => 6.5,
            'ph_max' => 9,
        ]);

        $this->postJson('/api/readings', $this->payload($device, ['ph' => 5.8]), [
            'X-Device-Token' => $token,
        ])->assertCreated();

        $this->assertDatabaseHas('alerts', [
            'device_id' => $device->id,
            'parameter' => 'ph',
            'value' => 5.8,
        ]);
    }

    public function test_device_without_configured_token_is_rejected(): void
    {
        $device = $this->createDevice('ESP32-NO-TOKEN');

        $this->postJson('/api/readings', $this->payload($device), [
            'X-Device-Token' => 'cualquier-token',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('readings', 0);
    }

    public function test_admin_of_farm_a_cannot_regenerate_token_of_farm_b_device(): void
    {
        [, , $foreignDevice] = $this->deviceWithOwner('farm-b@aqualytics.test');
        [$adminA] = $this->deviceWithOwner('farm-a@aqualytics.test');

        $this->actingAs($adminA)
            ->post(route('devices.regenerate-token', $foreignDevice))
            ->assertNotFound();
    }

    public function test_admin_can_regenerate_token_for_own_device(): void
    {
        [$admin, $token, $device] = $this->deviceWithOwner();
        $previousHash = $device->api_token_hash;

        $response = $this->actingAs($admin)
            ->from("/ponds/{$device->pond_id}")
            ->post(route('devices.regenerate-token', $device));

        $response->assertRedirect("/ponds/{$device->pond_id}");
        $response->assertSessionHas('device_token');
        $this->assertNotSame($previousHash, $device->fresh()->api_token_hash);
        $this->assertNotSame($token, session('device_token'));

        $this->postJson('/api/readings', $this->payload($device), [
            'X-Device-Token' => $token,
        ])->assertUnauthorized();
    }

    public function test_supervisor_cannot_regenerate_device_token(): void
    {
        [$admin, , $device] = $this->deviceWithOwner();
        $supervisor = User::factory()->create([
            'fish_farm_id' => $admin->fish_farm_id,
            'role' => User::ROLE_SUPERVISOR,
        ]);

        $this->actingAs($supervisor)
            ->post(route('devices.regenerate-token', $device))
            ->assertForbidden();
    }

    /**
     * @return array{0: Device, 1: string}
     */
    private function deviceWithToken(string $uid = 'ESP32-001'): array
    {
        $device = $this->createDevice($uid);
        $token = $device->issueToken();

        return [$device, $token];
    }

    /**
     * @return array{0: User, 1: string, 2: Device}
     */
    private function deviceWithOwner(string $email = 'admin@aqualytics.test'): array
    {
        $user = User::factory()->create([
            'email' => $email,
            'role' => User::ROLE_ADMIN,
        ]);
        $pond = $user->ponds()->create([
            'name' => 'Estanque '.$email,
            'code' => 'EST-'.substr(md5($email), 0, 6),
        ]);
        $device = $pond->devices()->create([
            'name' => 'ESP32 '.$email,
            'device_uid' => 'UID-'.substr(md5($email), 0, 8),
            'status' => 'active',
        ]);
        $token = $device->issueToken();

        return [$user, $token, $device];
    }

    private function createDevice(string $uid): Device
    {
        $user = User::factory()->create();
        $pond = $user->ponds()->create([
            'name' => 'Estanque 01',
            'code' => 'EST-'.substr(md5($uid), 0, 6),
        ]);

        return $pond->devices()->create([
            'name' => 'ESP32 '.$uid,
            'device_uid' => $uid,
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Device $device, array $overrides = []): array
    {
        return array_merge([
            'device_uid' => $device->device_uid,
            'temperature' => 25.6,
            'ph' => 7.2,
            'turbidity' => 34.5,
            'water_level' => 82.0,
        ], $overrides);
    }
}
