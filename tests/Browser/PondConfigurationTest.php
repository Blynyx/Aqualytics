<?php

namespace Tests\Browser;

use Database\Seeders\E2ETestSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PondConfigurationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(E2ETestSeeder::class);
    }

    public function test_admin_can_complete_pond_configuration(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/login')
                ->type('[data-cy="login-email"]', 'admin@cypress.test')
                ->type('[data-cy="login-password"]', 'password123')
                ->click('[data-cy="login-submit"]')
                ->waitForLocation('/dashboard')
                ->click('[data-cy="nav-ponds"]')
                ->waitForLocation('/ponds')
                ->click('[data-cy="new-pond"]')
                ->waitForLocation('/ponds/create')
                ->type('[data-cy="pond-name"]', 'Estanque Dusk')
                ->type('[data-cy="pond-code"]', 'DUSK-001')
                ->type('[data-cy="pond-species"]', 'Tilapia')
                ->type('[data-cy="pond-location"]', 'Zona Dusk')
                ->click('[data-cy="submit-pond"]')
                ->waitForText('Estanque Dusk')
                ->assertSee('DUSK-001')
                ->assertSee('Tilapia')
                ->assertSee('Zona Dusk')
                ->type('[data-cy="device-name"]', 'ESP32 Dusk')
                ->type('[data-cy="device-uid"]', 'DUSK-ESP32-001')
                ->waitForReload(function (Browser $browser): void {
                    $browser->click('[data-cy="submit-device"]');
                })
                ->assertSeeIn('[data-cy="device-card"]', 'ESP32 Dusk')
                ->assertSeeIn('[data-cy="device-card"]', 'DUSK-ESP32-001')
                ->type('[data-cy="temperature-min"]', '20')
                ->type('[data-cy="temperature-max"]', '32')
                ->type('[data-cy="ph-min"]', '6.5')
                ->type('[data-cy="ph-max"]', '9')
                ->type('[data-cy="turbidity-max"]', '100')
                ->type('[data-cy="water-level-min"]', '50')
                ->type('[data-cy="water-level-max"]', '100')
                ->waitForReload(function (Browser $browser): void {
                    $browser->click('[data-cy="submit-thresholds"]');
                })
                ->assertInputValue('[data-cy="temperature-min"]', '20')
                ->assertInputValue('[data-cy="temperature-max"]', '32')
                ->assertInputValue('[data-cy="ph-min"]', '6.5')
                ->assertInputValue('[data-cy="ph-max"]', '9')
                ->assertInputValue('[data-cy="turbidity-max"]', '100')
                ->assertInputValue('[data-cy="water-level-min"]', '50')
                ->assertInputValue('[data-cy="water-level-max"]', '100')
                ->screenshot('pond-configured');
        });
    }
}
