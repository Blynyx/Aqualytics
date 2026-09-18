<?php

namespace Tests\Feature;

use App\Models\FishFarm;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_returns_readings_for_pond(): void
    {
        [$user, $pond] = $this->farmPond();
        $this->createReading($pond, now()->subHour(), 25.4, 7.2, 31.5, 83.0);

        $response = $this
            ->actingAs($user)
            ->getJson(route('ponds.readings.history', $pond));

        $response->assertOk();
        $response->assertJsonPath('pond.id', $pond->id);
        $response->assertJsonPath('pond.name', $pond->name);
        $response->assertJsonPath('range', '24h');
        $response->assertJsonCount(1, 'readings');
        $response->assertJsonPath('readings.0.temperature', 25.4);
        $response->assertJsonPath('readings.0.ph', 7.2);
        $response->assertJsonPath('readings.0.turbidity', 31.5);
        $response->assertJsonPath('readings.0.water_level', 83.0);
    }

    public function test_history_orders_readings_chronologically(): void
    {
        [$user, $pond] = $this->farmPond();
        $this->createReading($pond, now()->subHours(2), 26.0);
        $this->createReading($pond, now()->subMinutes(10), 24.0);
        $this->createReading($pond, now()->subHour(), 25.0);

        $response = $this
            ->actingAs($user)
            ->getJson(route('ponds.readings.history', [$pond, 'range' => '24h']));

        $response->assertOk();
        $temperatures = collect($response->json('readings'))->pluck('temperature')->all();
        $this->assertSame([26.0, 25.0, 24.0], $temperatures);
        $timestamps = collect($response->json('readings'))->pluck('recorded_at')->all();
        $sorted = $timestamps;
        sort($sorted);
        $this->assertSame($sorted, $timestamps);
    }

    public function test_history_only_returns_requested_period(): void
    {
        [$user, $pond] = $this->farmPond();
        $this->createReading($pond, now()->subDays(3), 21.0);
        $this->createReading($pond, now()->subHours(3), 25.0);

        $response = $this
            ->actingAs($user)
            ->getJson(route('ponds.readings.history', [$pond, 'range' => '24h']));

        $response->assertOk();
        $response->assertJsonCount(1, 'readings');
        $response->assertJsonPath('readings.0.temperature', 25.0);
    }

    public function test_history_cannot_access_other_tenant_pond(): void
    {
        [, $foreignPond] = $this->farmPond('foreign@farm.test');
        [$user] = $this->farmPond('own@farm.test');
        $this->createReading($foreignPond, now()->subHour(), 25.0);

        $this
            ->actingAs($user)
            ->getJson(route('ponds.readings.history', $foreignPond))
            ->assertNotFound();
    }

    public function test_home_plan_allows_7_days(): void
    {
        [$user, $pond] = $this->homePond();
        $this->createReading($pond, now()->subDays(3), 24.5);

        $this
            ->actingAs($user)
            ->getJson(route('ponds.readings.history', [$pond, 'range' => '7d']))
            ->assertOk()
            ->assertJsonPath('range', '7d')
            ->assertJsonCount(1, 'readings');
    }

    public function test_home_plan_rejects_30_days(): void
    {
        [$user, $pond] = $this->homePond();
        $this->createReading($pond, now()->subHour(), 24.5);

        $this
            ->actingAs($user)
            ->getJson(route('ponds.readings.history', [$pond, 'range' => '30d']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('range');
    }

    public function test_farm_plan_allows_90_days(): void
    {
        [$user, $pond] = $this->farmPond();
        $this->createReading($pond, now()->subDays(40), 23.1);

        $this
            ->actingAs($user)
            ->getJson(route('ponds.readings.history', [$pond, 'range' => '90d']))
            ->assertOk()
            ->assertJsonPath('range', '90d')
            ->assertJsonCount(1, 'readings');
    }

    public function test_invalid_range_is_rejected(): void
    {
        [$user, $pond] = $this->farmPond();

        $this
            ->actingAs($user)
            ->getJson(route('ponds.readings.history', [$pond, 'range' => '365d']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('range');
    }

    public function test_home_pond_show_offers_only_plan_ranges(): void
    {
        [$user, $pond] = $this->homePond();

        $response = $this->actingAs($user)->get(route('ponds.show', $pond));

        $response->assertOk();
        $response->assertSee('Historial de mi pecera');
        $response->assertSee('data-cy="history-range-24h"', false);
        $response->assertSee('data-cy="history-range-7d"', false);
        $response->assertDontSee('data-cy="history-range-30d"', false);
        $response->assertDontSee('data-cy="history-range-90d"', false);
    }

    public function test_history_response_does_not_expose_sensitive_fields(): void
    {
        [$user, $pond] = $this->farmPond();
        $this->createReading($pond, now()->subHour(), 25.4, 7.2, 31.5, 83.0);

        $response = $this
            ->actingAs($user)
            ->getJson(route('ponds.readings.history', $pond));

        $response->assertOk();
        $this->assertSame(['id', 'name'], array_keys($response->json('pond')));
        $this->assertSame(
            ['recorded_at', 'temperature', 'ph', 'turbidity', 'water_level'],
            array_keys($response->json('readings.0')),
        );

        $payload = $response->getContent();
        $this->assertStringNotContainsString('fish_farm_id', $payload);
        $this->assertStringNotContainsString('user_id', $payload);
        $this->assertStringNotContainsString('password', $payload);
        $this->assertStringNotContainsString('APP_KEY', $payload);
    }

    /**
     * @return array{0: User, 1: Pond}
     */
    private function farmPond(string $email = 'farm@aqualytics.test'): array
    {
        $account = FishFarm::factory()->create([
            'account_type' => FishFarm::TYPE_FARM,
        ]);
        $user = User::factory()->create([
            'fish_farm_id' => $account->id,
            'email' => $email,
        ]);
        $pond = $account->ponds()->create([
            'user_id' => $user->id,
            'name' => 'Estanque Principal',
            'code' => 'EST-'.substr(md5($email), 0, 6),
            'unit_type' => Pond::TYPE_POND,
        ]);

        return [$user, $pond];
    }

    /**
     * @return array{0: User, 1: Pond}
     */
    private function homePond(): array
    {
        $account = FishFarm::factory()->create([
            'account_type' => FishFarm::TYPE_HOME,
        ]);
        $user = User::factory()->create([
            'fish_farm_id' => $account->id,
            'email' => 'home-history@aqualytics.test',
        ]);
        $pond = $account->ponds()->create([
            'user_id' => $user->id,
            'name' => 'Pecera Principal',
            'code' => 'PEC-HOME',
            'unit_type' => Pond::TYPE_AQUARIUM,
        ]);

        return [$user, $pond];
    }

    private function createReading(
        Pond $pond,
        $recordedAt,
        float $temperature = 25.0,
        float $ph = 7.2,
        float $turbidity = 30.0,
        float $waterLevel = 80.0,
    ): void {
        $device = $pond->devices()->first() ?? $pond->devices()->create([
            'name' => 'ESP32 '.$pond->code,
            'device_uid' => 'HIST-'.$pond->code,
            'status' => 'active',
        ]);

        $device->readings()->create([
            'pond_id' => $pond->id,
            'temperature' => $temperature,
            'ph' => $ph,
            'turbidity' => $turbidity,
            'water_level' => $waterLevel,
            'recorded_at' => $recordedAt,
        ]);
    }
}
