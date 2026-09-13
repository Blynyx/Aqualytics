<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PondWebManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_create_pond_form(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/ponds/create');

        $response->assertOk();
    }

    public function test_user_can_create_pond_from_web_form(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/ponds', [
                'name' => 'Estanque Web',
                'code' => 'EST-WEB-001',
                'species' => 'Tilapia',
                'location' => 'Zona Norte',
            ]);

        $pond = $user->ponds()->where('code', 'EST-WEB-001')->firstOrFail();

        $this->assertDatabaseHas('ponds', [
            'id' => $pond->id,
            'user_id' => $user->id,
            'name' => 'Estanque Web',
            'code' => 'EST-WEB-001',
        ]);
        $response->assertRedirect("/ponds/{$pond->id}");
    }

    public function test_user_can_view_own_pond_details(): void
    {
        $user = User::factory()->create();
        $pond = $user->ponds()->create([
            'name' => 'Estanque Principal',
            'code' => 'EST-001',
        ]);

        $response = $this
            ->actingAs($user)
            ->get("/ponds/{$pond->id}");

        $response->assertOk();
        $response->assertSee('Estanque Principal');
        $response->assertSee('EST-001');
    }

    public function test_user_cannot_view_another_users_pond(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $secondUsersPond = $secondUser->ponds()->create([
            'name' => 'Estanque Privado',
            'code' => 'EST-002',
        ]);

        $response = $this
            ->actingAs($firstUser)
            ->get("/ponds/{$secondUsersPond->id}");

        $response->assertNotFound();
    }

    public function test_pond_detail_displays_registered_device(): void
    {
        $user = User::factory()->create();
        $pond = $user->ponds()->create([
            'name' => 'Estanque Principal',
            'code' => 'EST-001',
        ]);
        $pond->devices()->create([
            'name' => 'ESP32 Principal',
            'device_uid' => 'ESP32-001',
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($user)
            ->get("/ponds/{$pond->id}");

        $response->assertSee('ESP32-001');
    }

    public function test_pond_detail_displays_latest_reading(): void
    {
        $user = User::factory()->create();
        $pond = $user->ponds()->create([
            'name' => 'Estanque Principal',
            'code' => 'EST-001',
        ]);
        $device = $pond->devices()->create([
            'name' => 'ESP32 Principal',
            'device_uid' => 'ESP32-001',
            'status' => 'active',
        ]);
        $device->readings()->create([
            'pond_id' => $pond->id,
            'temperature' => 25.6,
            'ph' => 7.2,
            'turbidity' => 34.5,
            'water_level' => 82,
            'recorded_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get("/ponds/{$pond->id}");

        $response->assertSee('25.6');
        $response->assertSee('7.2');
        $response->assertSee('34.5');
        $response->assertSee('82');
    }
}
