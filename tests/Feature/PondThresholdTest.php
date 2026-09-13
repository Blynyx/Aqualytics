<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PondThresholdTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_configure_thresholds_for_own_pond(): void
    {
        $user = User::factory()->create();
        $pond = $user->ponds()->create([
            'name' => 'Estanque 01',
            'code' => 'EST-001',
        ]);

        $response = $this
            ->actingAs($user)
            ->post("/ponds/{$pond->id}/thresholds", [
                'temperature_min' => 20,
                'temperature_max' => 32,
                'ph_min' => 6.5,
                'ph_max' => 9,
                'turbidity_max' => 100,
                'water_level_min' => 50,
                'water_level_max' => 100,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('pond_thresholds', [
            'pond_id' => $pond->id,
            'temperature_min' => 20,
            'temperature_max' => 32,
            'ph_min' => 6.5,
            'ph_max' => 9,
            'turbidity_max' => 100,
            'water_level_min' => 50,
            'water_level_max' => 100,
        ]);
    }

    public function test_user_cannot_configure_thresholds_for_another_fish_farms_pond(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $secondUsersPond = $secondUser->ponds()->create([
            'name' => 'Estanque de otro usuario',
            'code' => 'EST-002',
        ]);

        $response = $this
            ->actingAs($firstUser)
            ->post("/ponds/{$secondUsersPond->id}/thresholds", [
                'ph_min' => 6.5,
                'ph_max' => 9,
            ]);

        $response->assertNotFound();

        $this->assertDatabaseMissing('pond_thresholds', [
            'pond_id' => $secondUsersPond->id,
        ]);
    }

    public function test_minimum_threshold_cannot_be_greater_than_maximum(): void
    {
        $user = User::factory()->create();
        $pond = $user->ponds()->create([
            'name' => 'Estanque 01',
            'code' => 'EST-001',
        ]);

        $response = $this
            ->actingAs($user)
            ->post("/ponds/{$pond->id}/thresholds", [
                'ph_min' => 9,
                'ph_max' => 6,
            ]);

        $response->assertSessionHasErrors('ph_max');

        $this->assertDatabaseMissing('pond_thresholds', [
            'pond_id' => $pond->id,
        ]);
    }
}
