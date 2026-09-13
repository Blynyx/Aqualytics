<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PondTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_pond(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/ponds', [
                'name' => 'Estanque 01',
                'code' => 'EST-001',
                'species' => 'Tilapia',
                'location' => 'Zona A',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('ponds', [
            'user_id' => $user->id,
            'name' => 'Estanque 01',
            'code' => 'EST-001',
            'species' => 'Tilapia',
            'location' => 'Zona A',
        ]);
    }

    public function test_pond_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/ponds', [
                'code' => 'EST-002',
                'species' => 'Tilapia',
                'location' => 'Zona B',
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('ponds', [
            'code' => 'EST-002',
        ]);
    }

    public function test_user_can_only_see_their_own_ponds(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $firstUser->ponds()->create([
            'name' => 'Estanque del primer usuario',
            'code' => 'EST-101',
        ]);

        $secondUser->ponds()->create([
            'name' => 'Estanque del segundo usuario',
            'code' => 'EST-202',
        ]);

        $response = $this
            ->actingAs($firstUser)
            ->get('/ponds');

        $response->assertOk();
        $response->assertSee('Estanque del primer usuario');
        $response->assertDontSee('Estanque del segundo usuario');
    }
}