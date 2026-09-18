<?php

namespace Tests\Feature;

use App\Models\FishFarm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_can_create_first_aquarium(): void
    {
        $admin = $this->homeAdmin();

        $response = $this
            ->actingAs($admin)
            ->post('/ponds', [
                'name' => 'Pecera Sala',
                'code' => 'PEC-001',
            ]);

        $pond = $admin->fishFarm->ponds()->where('code', 'PEC-001')->firstOrFail();

        $response->assertRedirect("/ponds/{$pond->id}");
        $this->assertSame(1, $admin->fishFarm->ponds()->count());
    }

    public function test_home_cannot_create_second_aquarium(): void
    {
        $admin = $this->homeAdmin();

        $this->actingAs($admin)->post('/ponds', [
            'name' => 'Pecera Sala',
            'code' => 'PEC-001',
        ])->assertRedirect();

        $response = $this->actingAs($admin)->post('/ponds', [
            'name' => 'Pecera Extra',
            'code' => 'PEC-002',
        ]);

        $response->assertSessionHasErrors();
        $this->assertSame(1, $admin->fishFarm->ponds()->count());
    }

    public function test_home_can_register_first_device(): void
    {
        $admin = $this->homeAdmin();
        $pond = $admin->fishFarm->ponds()->create([
            'user_id' => $admin->id,
            'name' => 'Pecera Sala',
            'code' => 'PEC-001',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post('/devices', [
                'pond_id' => $pond->id,
                'name' => 'ESP32 Home',
                'device_uid' => 'HOME-ESP32-001',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('devices', [
            'pond_id' => $pond->id,
            'device_uid' => 'HOME-ESP32-001',
        ]);
    }

    public function test_home_cannot_register_second_device(): void
    {
        $admin = $this->homeAdmin();
        $pond = $admin->fishFarm->ponds()->create([
            'user_id' => $admin->id,
            'name' => 'Pecera Sala',
            'code' => 'PEC-001',
        ]);
        $pond->devices()->create([
            'name' => 'ESP32 Home',
            'device_uid' => 'HOME-ESP32-001',
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post('/devices', [
                'pond_id' => $pond->id,
                'name' => 'ESP32 Extra',
                'device_uid' => 'HOME-ESP32-002',
            ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('devices', 1);
    }

    public function test_home_cannot_create_additional_user(): void
    {
        $admin = $this->homeAdmin();

        $response = $this
            ->actingAs($admin)
            ->post('/users', [
                'name' => 'Segundo Usuario',
                'email' => 'segundo@home.test',
                'role' => User::ROLE_SUPERVISOR,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertSessionHasErrors();
        $this->assertSame(1, $admin->fishFarm->users()->count());
    }

    public function test_farm_can_create_multiple_ponds_within_plan_limit(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post('/ponds', [
            'name' => 'Estanque 1',
            'code' => 'EST-001',
        ])->assertRedirect();

        $this->actingAs($admin)->post('/ponds', [
            'name' => 'Estanque 2',
            'code' => 'EST-002',
        ])->assertRedirect();

        $this->assertSame(2, $admin->fishFarm->ponds()->count());
    }

    public function test_different_accounts_have_independent_limits(): void
    {
        $firstHome = $this->homeAdmin('one@home.test');
        $secondHome = $this->homeAdmin('two@home.test');

        $this->actingAs($firstHome)->post('/ponds', [
            'name' => 'Pecera Uno',
            'code' => 'PEC-001',
        ])->assertRedirect();

        $this->actingAs($secondHome)->post('/ponds', [
            'name' => 'Pecera Dos',
            'code' => 'PEC-001',
        ])->assertRedirect();

        $this->assertSame(1, $firstHome->fishFarm->ponds()->count());
        $this->assertSame(1, $secondHome->fishFarm->ponds()->count());
    }

    private function homeAdmin(string $email = 'home@aqualytics.test'): User
    {
        $account = FishFarm::factory()->create([
            'name' => 'Cuenta Home '.$email,
            'account_type' => FishFarm::TYPE_HOME,
        ]);

        return User::factory()->create([
            'fish_farm_id' => $account->id,
            'email' => $email,
            'role' => User::ROLE_ADMIN,
        ]);
    }
}
