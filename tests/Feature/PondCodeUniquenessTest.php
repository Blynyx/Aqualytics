<?php

namespace Tests\Feature;

use App\Models\FishFarm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PondCodeUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_pond_code_in_their_fish_farm(): void
    {
        $fishFarm = FishFarm::factory()->create();
        $admin = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post('/ponds', [
                'name' => 'Estanque Alpha',
                'code' => 'EST-001',
                'species' => 'Tilapia',
                'location' => 'Zona A',
            ]);

        $pond = $fishFarm->ponds()->where('code', 'EST-001')->firstOrFail();

        $response->assertRedirect("/ponds/{$pond->id}");
        $this->assertDatabaseHas('ponds', [
            'id' => $pond->id,
            'fish_farm_id' => $fishFarm->id,
            'user_id' => $admin->id,
            'code' => 'EST-001',
        ]);
    }

    public function test_duplicate_pond_code_is_rejected_inside_the_same_fish_farm(): void
    {
        $fishFarm = FishFarm::factory()->create();
        $firstAdmin = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_ADMIN,
        ]);
        $secondAdmin = User::factory()->create([
            'fish_farm_id' => $fishFarm->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $this
            ->actingAs($firstAdmin)
            ->post('/ponds', [
                'name' => 'Estanque Alpha',
                'code' => 'EST-001',
            ])
            ->assertRedirect();

        $response = $this
            ->actingAs($secondAdmin)
            ->post('/ponds', [
                'name' => 'Estanque Duplicado',
                'code' => 'EST-001',
            ]);

        $response->assertSessionHasErrors('code');
        $this->assertSame(1, $fishFarm->ponds()->where('code', 'EST-001')->count());
    }

    public function test_same_pond_code_is_allowed_in_a_different_fish_farm(): void
    {
        $firstFishFarm = FishFarm::factory()->create();
        $secondFishFarm = FishFarm::factory()->create();
        $firstAdmin = User::factory()->create([
            'fish_farm_id' => $firstFishFarm->id,
            'role' => User::ROLE_ADMIN,
        ]);
        $secondAdmin = User::factory()->create([
            'fish_farm_id' => $secondFishFarm->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $this
            ->actingAs($firstAdmin)
            ->post('/ponds', [
                'name' => 'Estanque Alpha',
                'code' => 'EST-001',
            ])
            ->assertRedirect();

        $response = $this
            ->actingAs($secondAdmin)
            ->post('/ponds', [
                'name' => 'Estanque Bravo',
                'code' => 'EST-001',
            ]);

        $secondPond = $secondFishFarm->ponds()->where('code', 'EST-001')->firstOrFail();

        $response->assertRedirect("/ponds/{$secondPond->id}");
        $this->assertSame(1, $firstFishFarm->ponds()->where('code', 'EST-001')->count());
        $this->assertSame(1, $secondFishFarm->ponds()->where('code', 'EST-001')->count());
    }

    public function test_created_ponds_belong_to_the_authenticated_users_fish_farm(): void
    {
        $firstFishFarm = FishFarm::factory()->create();
        $secondFishFarm = FishFarm::factory()->create();
        $admin = User::factory()->create([
            'fish_farm_id' => $firstFishFarm->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $this
            ->actingAs($admin)
            ->post('/ponds', [
                'name' => 'Estanque Inyectado',
                'code' => 'EST-INY',
                'fish_farm_id' => $secondFishFarm->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ponds', [
            'code' => 'EST-INY',
            'fish_farm_id' => $firstFishFarm->id,
            'user_id' => $admin->id,
        ]);
        $this->assertDatabaseMissing('ponds', [
            'code' => 'EST-INY',
            'fish_farm_id' => $secondFishFarm->id,
        ]);
    }
}
