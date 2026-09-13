<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
    }

    public function test_dashboard_only_shows_authenticated_users_data(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $firstUser->ponds()->create([
            'name' => 'Estanque exclusivo del usuario uno',
            'code' => 'EST-001',
        ]);
        $secondUser->ponds()->create([
            'name' => 'Estanque privado del usuario dos',
            'code' => 'EST-002',
        ]);

        $response = $this
            ->actingAs($firstUser)
            ->get('/dashboard');

        $response->assertSee('Estanque exclusivo del usuario uno');
        $response->assertDontSee('Estanque privado del usuario dos');
    }

    public function test_dashboard_shows_active_alerts_for_authenticated_user(): void
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
        $reading = $device->readings()->create([
            'pond_id' => $pond->id,
            'temperature' => 25.6,
            'ph' => 5.8,
            'turbidity' => 34.5,
            'water_level' => 82,
            'recorded_at' => now(),
        ]);
        $reading->alerts()->create([
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

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertSee('pH por debajo del rango configurado');
    }
}
