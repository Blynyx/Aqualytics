<?php

namespace Tests\Browser;

use App\Models\Pond;
use Database\Seeders\E2ETestSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ThresholdValidationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(E2ETestSeeder::class);
    }

    public function test_invalid_ph_range_is_rejected_without_replacing_valid_values(): void
    {
        $pond = Pond::where('code', 'CYP-001')->firstOrFail();

        $this->browse(function (Browser $browser) use ($pond): void {
            $browser->visit('/login')
                ->type('[data-cy="login-email"]', 'admin@cypress.test')
                ->type('[data-cy="login-password"]', 'password123')
                ->click('[data-cy="login-submit"]')
                ->waitForLocation('/dashboard')
                ->visit("/ponds/{$pond->id}")
                ->assertSee('Estanque Cypress')
                ->clear('[data-cy="ph-min"]')
                ->type('[data-cy="ph-min"]', '9')
                ->clear('[data-cy="ph-max"]')
                ->type('[data-cy="ph-max"]', '6')
                ->click('[data-cy="submit-thresholds"]')
                ->waitFor('[data-cy="ph-max-error"]')
                ->assertPathIs("/ponds/{$pond->id}")
                ->assertVisible('[data-cy="ph-max-error"]');

            $browser->script(
                'document.querySelector(\'[data-cy="ph-max-error"]\').scrollIntoView({block: "center"});'
            );
            $browser->screenshot('threshold-validation');

            $this->assertDatabaseHas('pond_thresholds', [
                'pond_id' => $pond->id,
                'ph_min' => 6.5,
                'ph_max' => 9,
            ]);

            $browser->visit("/ponds/{$pond->id}")
                ->assertInputValue('[data-cy="ph-min"]', '6.5')
                ->assertInputValue('[data-cy="ph-max"]', '9');
        });
    }
}
