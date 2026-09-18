<?php

namespace Tests\Feature;

use App\Models\FishFarm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_account_can_be_created(): void
    {
        $account = FishFarm::create([
            'name' => 'Acuario Casa',
            'status' => 'active',
            'account_type' => FishFarm::TYPE_HOME,
        ]);

        $this->assertDatabaseHas('fish_farms', [
            'id' => $account->id,
            'name' => 'Acuario Casa',
            'account_type' => 'home',
        ]);
        $this->assertSame(FishFarm::TYPE_HOME, $account->account_type);
    }

    public function test_farm_account_can_be_created(): void
    {
        $account = FishFarm::create([
            'name' => 'Piscigranja Los Andes',
            'status' => 'active',
            'account_type' => FishFarm::TYPE_FARM,
        ]);

        $this->assertDatabaseHas('fish_farms', [
            'id' => $account->id,
            'name' => 'Piscigranja Los Andes',
            'account_type' => 'farm',
        ]);
        $this->assertSame(FishFarm::TYPE_FARM, $account->account_type);
    }

    public function test_invalid_account_type_is_rejected_in_registration(): void
    {
        $response = $this->post('/register', [
            'account_type' => 'enterprise123',
            'fish_farm_name' => 'Cuenta invalida',
            'name' => 'Usuario Invalido',
            'email' => 'invalido@aqualytics.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('account_type');
        $this->assertDatabaseMissing('users', [
            'email' => 'invalido@aqualytics.test',
        ]);
        $this->assertDatabaseMissing('fish_farms', [
            'name' => 'Cuenta invalida',
        ]);
    }
}
