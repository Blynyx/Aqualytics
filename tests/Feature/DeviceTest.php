<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_register_device_for_own_pond(): void
    {
        $user = User::factory()->create();
        $pond = $user->ponds()->create([
            'name' => 'Estanque 01',
            'code' => 'EST-001',
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/devices', [
                'pond_id' => $pond->id,
                'name' => 'ESP32 Estanque 01',
                'device_uid' => 'ESP32-001',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('devices', [
            'pond_id' => $pond->id,
            'name' => 'ESP32 Estanque 01',
            'device_uid' => 'ESP32-001',
            'status' => 'active',
        ]);
    }

    public function test_device_uid_is_required(): void
    {
        $user = User::factory()->create();
        $pond = $user->ponds()->create([
            'name' => 'Estanque 01',
            'code' => 'EST-001',
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/devices', [
                'pond_id' => $pond->id,
                'name' => 'ESP32 Estanque 01',
            ]);

        $response->assertSessionHasErrors('device_uid');

        $this->assertDatabaseMissing('devices', [
            'name' => 'ESP32 Estanque 01',
        ]);
    }

    public function test_device_uid_must_be_unique(): void
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

        $response = $this
            ->actingAs($user)
            ->post('/devices', [
                'pond_id' => $pond->id,
                'name' => 'ESP32 Estanque 02',
                'device_uid' => 'ESP32-001',
            ]);

        $response->assertSessionHasErrors('device_uid');

        $this->assertDatabaseCount('devices', 1);
    }

    public function test_user_cannot_register_device_for_another_users_pond(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $secondUsersPond = $secondUser->ponds()->create([
            'name' => 'Estanque de otro usuario',
            'code' => 'EST-002',
        ]);

        $response = $this
            ->actingAs($firstUser)
            ->post('/devices', [
                'pond_id' => $secondUsersPond->id,
                'name' => 'ESP32 no autorizado',
                'device_uid' => 'ESP32-002',
            ]);

        $response->assertSessionHasErrors('pond_id');

        $this->assertDatabaseMissing('devices', [
            'device_uid' => 'ESP32-002',
        ]);
    }
}
