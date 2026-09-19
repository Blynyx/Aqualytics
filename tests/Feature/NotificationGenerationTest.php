<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\FishFarm;
use App\Models\Incident;
use App\Models\InternalNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_created_notifies_farm_admin_and_supervisor(): void
    {
        $context = $this->farmWithDeviceAndThreshold();

        $this->postAuthenticatedReading($context['device'], $context['token'], ['ph' => 5.8])
            ->assertCreated();

        $alert = Alert::query()->firstOrFail();

        $this->assertDatabaseHas('internal_notifications', [
            'user_id' => $context['admin']->id,
            'fish_farm_id' => $context['farm']->id,
            'type' => InternalNotification::TYPE_ALERT_CREATED,
            'source_type' => InternalNotification::SOURCE_ALERT,
            'source_id' => $alert->id,
        ]);
        $this->assertDatabaseHas('internal_notifications', [
            'user_id' => $context['supervisor']->id,
            'fish_farm_id' => $context['farm']->id,
            'type' => InternalNotification::TYPE_ALERT_CREATED,
            'source_id' => $alert->id,
        ]);
        $this->assertSame(2, InternalNotification::query()->count());
    }

    public function test_alert_created_does_not_notify_specialist(): void
    {
        $context = $this->farmWithDeviceAndThreshold();

        $this->postAuthenticatedReading($context['device'], $context['token'], ['ph' => 5.8])
            ->assertCreated();

        $this->assertDatabaseMissing('internal_notifications', [
            'user_id' => $context['specialist']->id,
        ]);
    }

    public function test_home_alert_created_notifies_admin(): void
    {
        $account = FishFarm::factory()->create([
            'account_type' => FishFarm::TYPE_HOME,
        ]);
        $admin = User::factory()->create([
            'fish_farm_id' => $account->id,
            'role' => User::ROLE_ADMIN,
        ]);
        [$device, $token] = $this->deviceOnAccount($account, $admin);

        $this->postAuthenticatedReading($device, $token, ['ph' => 5.8])
            ->assertCreated();

        $alert = Alert::query()->firstOrFail();

        $this->assertDatabaseHas('internal_notifications', [
            'user_id' => $admin->id,
            'fish_farm_id' => $account->id,
            'type' => InternalNotification::TYPE_ALERT_CREATED,
            'source_id' => $alert->id,
        ]);
        $this->assertSame(1, InternalNotification::query()->count());
    }

    public function test_alert_assignment_notifies_only_assigned_specialist(): void
    {
        $context = $this->farmWithRoles();
        $alert = $this->createAlert($context['farm'], $context['supervisor']);
        $otherSpecialist = User::factory()->create([
            'fish_farm_id' => $context['farm']->id,
            'role' => User::ROLE_SPECIALIST,
        ]);

        $this->actingAs($context['supervisor'])
            ->post("/alerts/{$alert->id}/assign", [
                'specialist_id' => $context['specialist']->id,
            ])
            ->assertRedirect("/ponds/{$alert->pond_id}");

        $this->assertDatabaseHas('internal_notifications', [
            'user_id' => $context['specialist']->id,
            'type' => InternalNotification::TYPE_ALERT_ASSIGNED,
            'source_type' => InternalNotification::SOURCE_ALERT,
            'source_id' => $alert->id,
        ]);
        $this->assertDatabaseMissing('internal_notifications', [
            'user_id' => $context['supervisor']->id,
            'type' => InternalNotification::TYPE_ALERT_ASSIGNED,
        ]);
        $this->assertDatabaseMissing('internal_notifications', [
            'user_id' => $context['admin']->id,
            'type' => InternalNotification::TYPE_ALERT_ASSIGNED,
        ]);
        $this->assertDatabaseMissing('internal_notifications', [
            'user_id' => $otherSpecialist->id,
        ]);
        $this->assertSame(1, InternalNotification::query()->count());
    }

    public function test_incident_assignment_notifies_only_assigned_specialist(): void
    {
        $context = $this->farmWithRoles();
        $alert = $this->createAlert($context['farm'], $context['supervisor']);
        $incident = Incident::query()->create([
            'alert_id' => $alert->id,
            'fish_farm_id' => $context['farm']->id,
            'created_by' => $context['supervisor']->id,
            'title' => 'Revisar temperatura del estanque',
            'description' => 'Requiere seguimiento operativo del pH.',
            'status' => Incident::STATUS_OPEN,
        ]);

        $this->actingAs($context['supervisor'])
            ->post(route('incidents.assign', $incident), [
                'specialist_id' => $context['specialist']->id,
            ])
            ->assertRedirect(route('incidents.show', $incident));

        $this->assertDatabaseHas('internal_notifications', [
            'user_id' => $context['specialist']->id,
            'type' => InternalNotification::TYPE_INCIDENT_ASSIGNED,
            'source_type' => InternalNotification::SOURCE_INCIDENT,
            'source_id' => $incident->id,
        ]);
        $this->assertDatabaseMissing('internal_notifications', [
            'user_id' => $context['supervisor']->id,
        ]);
        $this->assertDatabaseMissing('internal_notifications', [
            'user_id' => $context['admin']->id,
        ]);
        $this->assertSame(1, InternalNotification::query()->count());
    }

    public function test_notification_generation_does_not_change_alert_status(): void
    {
        $context = $this->farmWithDeviceAndThreshold();

        $this->postAuthenticatedReading($context['device'], $context['token'], ['ph' => 5.8])
            ->assertCreated();

        $this->assertDatabaseHas('alerts', [
            'status' => 'active',
        ]);
        $this->assertTrue(InternalNotification::query()->exists());
    }

    public function test_notification_generation_does_not_change_incident_status(): void
    {
        $context = $this->farmWithRoles();
        $alert = $this->createAlert($context['farm'], $context['supervisor']);
        $incident = Incident::query()->create([
            'alert_id' => $alert->id,
            'fish_farm_id' => $context['farm']->id,
            'created_by' => $context['supervisor']->id,
            'title' => 'Incidencia de pH',
            'description' => 'Requiere seguimiento operativo.',
            'status' => Incident::STATUS_OPEN,
        ]);

        $this->actingAs($context['supervisor'])
            ->post(route('incidents.assign', $incident), [
                'specialist_id' => $context['specialist']->id,
            ])
            ->assertRedirect();

        $this->assertSame(Incident::STATUS_ASSIGNED, $incident->fresh()->status);
        $this->assertTrue(InternalNotification::query()->exists());
    }

    /**
     * @return array{farm: FishFarm, admin: User, supervisor: User, specialist: User, device: mixed, token: string}
     */
    private function farmWithDeviceAndThreshold(): array
    {
        $context = $this->farmWithRoles();
        [$device, $token] = $this->deviceOnAccount($context['farm'], $context['admin']);

        return array_merge($context, [
            'device' => $device,
            'token' => $token,
        ]);
    }

    /**
     * @return array{farm: FishFarm, admin: User, supervisor: User, specialist: User}
     */
    private function farmWithRoles(): array
    {
        $farm = FishFarm::factory()->create([
            'account_type' => FishFarm::TYPE_FARM,
        ]);
        $admin = User::factory()->create([
            'fish_farm_id' => $farm->id,
            'role' => User::ROLE_ADMIN,
        ]);
        $supervisor = User::factory()->create([
            'fish_farm_id' => $farm->id,
            'role' => User::ROLE_SUPERVISOR,
        ]);
        $specialist = User::factory()->create([
            'fish_farm_id' => $farm->id,
            'role' => User::ROLE_SPECIALIST,
        ]);

        return compact('farm', 'admin', 'supervisor', 'specialist');
    }

    private function deviceOnAccount(FishFarm $account, User $owner): array
    {
        $pond = $account->ponds()->create([
            'user_id' => $owner->id,
            'name' => 'Estanque Norte',
            'code' => 'EST-NTE-'.$owner->id,
        ]);
        $pond->threshold()->create([
            'ph_min' => 6.5,
            'ph_max' => 9,
        ]);
        $device = $pond->devices()->create([
            'name' => 'ESP32 Norte',
            'device_uid' => 'ESP32-NTE-'.$owner->id,
            'status' => 'active',
        ]);

        return [$device, $device->issueToken()];
    }

    private function postAuthenticatedReading($device, string $token, array $overrides = [])
    {
        return $this->postJson('/api/readings', array_merge([
            'device_uid' => $device->device_uid,
            'temperature' => 25.6,
            'ph' => 7.2,
            'turbidity' => 34.5,
            'water_level' => 82.0,
        ], $overrides), [
            'X-Device-Token' => $token,
        ]);
    }

    private function createAlert(FishFarm $fishFarm, User $creator): Alert
    {
        $pond = $fishFarm->ponds()->create([
            'user_id' => $creator->id,
            'name' => 'Estanque Norte',
            'code' => 'EST-ALR-'.$creator->id,
        ]);
        $device = $pond->devices()->create([
            'name' => 'ESP32 alerta',
            'device_uid' => 'ESP32-ALR-'.$creator->id,
            'status' => 'active',
        ]);
        $reading = $device->readings()->create([
            'pond_id' => $pond->id,
            'temperature' => 25.6,
            'ph' => 5.8,
            'turbidity' => 34.5,
            'water_level' => 82,
            'recorded_at' => now(),
        ]);

        return $reading->alerts()->create([
            'pond_id' => $pond->id,
            'device_id' => $device->id,
            'parameter' => 'ph',
            'value' => 5.8,
            'min_threshold' => 6.5,
            'severity' => 'warning',
            'status' => 'active',
            'message' => 'pH por debajo del rango configurado',
            'detected_at' => now(),
        ]);
    }
}
