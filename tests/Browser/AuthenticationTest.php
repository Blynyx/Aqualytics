<?php

namespace Tests\Browser;

use Database\Seeders\E2ETestSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AuthenticationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(E2ETestSeeder::class);
    }

    public function test_admin_can_login_and_view_dashboard(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/login')
                ->waitFor('[data-cy="login-email"]')
                ->type('[data-cy="login-email"]', 'admin@cypress.test')
                ->type('[data-cy="login-password"]', 'password123')
                ->click('[data-cy="login-submit"]')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard')
                ->assertSeeIn('[data-cy="brand"]', 'Aqualytics')
                ->assertSeeIn('[data-cy="fish-farm-name"]', 'Piscigranja Cypress')
                ->assertSeeIn('[data-cy="user-role"]', 'Administrador')
                ->screenshot('dashboard-admin');
        });
    }
}
