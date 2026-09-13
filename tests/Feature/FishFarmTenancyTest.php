<?php

namespace Tests\Feature;

use App\Models\FishFarm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FishFarmTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_fish_farm_and_admin_user(): void
    {
        $response = $this->post('/register', [
            'fish_farm_name' => 'Piscigranja Los Andes',
            'name' => 'Administrador',
            'email' => 'admin@losandes.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('fish_farms', [
            'name' => 'Piscigranja Los Andes',
        ]);

        $fishFarm = FishFarm::where('name', 'Piscigranja Los Andes')->firstOrFail();
        $user = User::where('email', 'admin@losandes.test')->firstOrFail();

        $this->assertSame($fishFarm->id, $user->fish_farm_id);
        $this->assertSame(User::ROLE_ADMIN, $user->role);
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_users_from_same_fish_farm_can_access_same_pond(): void
    {
        $fishFarm = FishFarm::factory()->create();
        $admin = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_ADMIN,
        ]);
        $supervisor = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_SUPERVISOR,
        ]);
        $pond = $fishFarm->ponds()->create([
            'user_id' => $admin->id,
            'name' => 'Estanque Compartido',
            'code' => 'EST-COMPARTIDO',
        ]);

        $response = $this
            ->actingAs($supervisor)
            ->get("/ponds/{$pond->id}");

        $response->assertOk();
        $response->assertSee('Estanque Compartido');
    }

    public function test_user_cannot_access_pond_from_another_fish_farm(): void
    {
        $firstFishFarm = FishFarm::factory()->create();
        $secondFishFarm = FishFarm::factory()->create();
        $firstUser = User::factory()->create([
            'fish_farm_id' => $firstFishFarm->id,
        ]);
        $secondUser = User::factory()->create([
            'fish_farm_id' => $secondFishFarm->id,
        ]);
        $secondPond = $secondFishFarm->ponds()->create([
            'user_id' => $secondUser->id,
            'name' => 'Estanque de otra piscigranja',
            'code' => 'EST-OTRA',
        ]);

        $response = $this
            ->actingAs($firstUser)
            ->get("/ponds/{$secondPond->id}");

        $response->assertNotFound();
    }

    public function test_dashboard_only_contains_data_from_authenticated_users_fish_farm(): void
    {
        $firstFishFarm = FishFarm::factory()->create();
        $secondFishFarm = FishFarm::factory()->create();
        $firstUser = User::factory()->create([
            'fish_farm_id' => $firstFishFarm->id,
        ]);
        $secondUser = User::factory()->create([
            'fish_farm_id' => $secondFishFarm->id,
        ]);

        $firstFishFarm->ponds()->create([
            'user_id' => $firstUser->id,
            'name' => 'Estanque Piscigranja A',
            'code' => 'EST-A',
        ]);
        $secondFishFarm->ponds()->create([
            'user_id' => $secondUser->id,
            'name' => 'Estanque Piscigranja B',
            'code' => 'EST-B',
        ]);

        $response = $this
            ->actingAs($firstUser)
            ->get('/dashboard');

        $response->assertSee('Estanque Piscigranja A');
        $response->assertDontSee('Estanque Piscigranja B');
    }

    public function test_user_role_can_be_admin_supervisor_or_specialist(): void
    {
        $fishFarm = FishFarm::factory()->create();

        User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'email' => 'admin@roles.test',
            'role' => User::ROLE_ADMIN,
        ]);
        User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'email' => 'supervisor@roles.test',
            'role' => User::ROLE_SUPERVISOR,
        ]);
        User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'email' => 'specialist@roles.test',
            'role' => User::ROLE_SPECIALIST,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@roles.test',
            'role' => 'admin',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'supervisor@roles.test',
            'role' => 'supervisor',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'specialist@roles.test',
            'role' => 'specialist',
        ]);
    }
}
