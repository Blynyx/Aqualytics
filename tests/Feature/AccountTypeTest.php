<?php

namespace Tests\Feature;

use App\Models\FishFarm;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_account_creates_aquarium_units(): void
    {
        $account = FishFarm::factory()->create([
            'account_type' => FishFarm::TYPE_HOME,
        ]);
        $admin = User::factory()->create([
            'fish_farm_id' => $account->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)->post('/ponds', [
            'name' => 'Pecera Principal',
            'code' => 'PEC-HOME',
            'unit_type' => Pond::TYPE_POND,
        ])->assertRedirect();

        $this->assertDatabaseHas('ponds', [
            'fish_farm_id' => $account->id,
            'code' => 'PEC-HOME',
            'unit_type' => Pond::TYPE_AQUARIUM,
        ]);
    }

    public function test_farm_account_creates_pond_units(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)->post('/ponds', [
            'name' => 'Estanque Principal',
            'code' => 'EST-FARM',
            'unit_type' => Pond::TYPE_AQUARIUM,
        ])->assertRedirect();

        $this->assertDatabaseHas('ponds', [
            'fish_farm_id' => $admin->fish_farm_id,
            'code' => 'EST-FARM',
            'unit_type' => Pond::TYPE_POND,
        ]);
        $this->assertSame(FishFarm::TYPE_FARM, $admin->fishFarm->account_type);
    }

    public function test_existing_accounts_default_to_farm_and_pond(): void
    {
        $account = FishFarm::factory()->create();
        $admin = User::factory()->create([
            'fish_farm_id' => $account->id,
        ]);
        $pond = $account->ponds()->create([
            'user_id' => $admin->id,
            'name' => 'Estanque legado',
            'code' => 'EST-LEGADO',
        ]);

        $account->refresh();
        $pond->refresh();

        $this->assertSame(FishFarm::TYPE_FARM, $account->account_type);
        $this->assertSame(Pond::TYPE_POND, $pond->unit_type);
    }
}
