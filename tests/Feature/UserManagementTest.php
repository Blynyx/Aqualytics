<?php

namespace Tests\Feature;

use App\Models\FishFarm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_users_from_own_fish_farm(): void
    {
        $firstFishFarm = FishFarm::factory()->create();
        $admin = User::factory()->create([
            'fish_farm_id' => $firstFishFarm->id,
            'role' => User::ROLE_ADMIN,
        ]);
        User::factory()->create([
            'fish_farm_id' => $firstFishFarm->id,
            'name' => 'Supervisor Piscigranja A',
            'role' => User::ROLE_SUPERVISOR,
        ]);

        $secondFishFarm = FishFarm::factory()->create();
        User::factory()->create([
            'fish_farm_id' => $secondFishFarm->id,
            'name' => 'Usuario Piscigranja B',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/users');

        $response->assertOk();
        $response->assertSee('Supervisor Piscigranja A');
        $response->assertDontSee('Usuario Piscigranja B');
    }

    public function test_admin_can_create_supervisor_for_own_fish_farm(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post('/users', [
                'name' => 'Supervisor Uno',
                'email' => 'supervisor@aqualytics.test',
                'role' => 'supervisor',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $this->assertDatabaseHas('users', [
            'fish_farm_id' => $admin->fish_farm_id,
            'name' => 'Supervisor Uno',
            'email' => 'supervisor@aqualytics.test',
            'role' => User::ROLE_SUPERVISOR,
        ]);
        $response->assertRedirect('/users');
    }

    public function test_admin_can_create_specialist_for_own_fish_farm(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post('/users', [
                'name' => 'Especialista Uno',
                'email' => 'especialista@aqualytics.test',
                'role' => 'specialist',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $this->assertDatabaseHas('users', [
            'fish_farm_id' => $admin->fish_farm_id,
            'name' => 'Especialista Uno',
            'email' => 'especialista@aqualytics.test',
            'role' => User::ROLE_SPECIALIST,
        ]);
        $response->assertRedirect('/users');
    }

    public function test_admin_cannot_select_invalid_role_when_creating_user(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post('/users', [
                'name' => 'Superadmin No Permitido',
                'email' => 'superadmin@aqualytics.test',
                'role' => 'superadmin',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', [
            'email' => 'superadmin@aqualytics.test',
        ]);
    }

    public function test_supervisor_cannot_access_user_management(): void
    {
        $supervisor = User::factory()->create([
            'role' => User::ROLE_SUPERVISOR,
        ]);

        $response = $this
            ->actingAs($supervisor)
            ->get('/users');

        $response->assertForbidden();
    }

    public function test_specialist_cannot_access_user_management(): void
    {
        $specialist = User::factory()->create([
            'role' => User::ROLE_SPECIALIST,
        ]);

        $response = $this
            ->actingAs($specialist)
            ->get('/users');

        $response->assertForbidden();
    }
}
