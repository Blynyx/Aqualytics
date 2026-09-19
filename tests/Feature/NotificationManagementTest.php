<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\FishFarm;
use App\Models\Incident;
use App\Models\InternalNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_index_only_shows_authenticated_users_notifications(): void
    {
        $farm = FishFarm::factory()->create();
        $admin = User::factory()->create([
            'fish_farm_id' => $farm->id,
            'role' => User::ROLE_ADMIN,
        ]);
        $supervisor = User::factory()->create([
            'fish_farm_id' => $farm->id,
            'role' => User::ROLE_SUPERVISOR,
        ]);
        $own = $this->notificationFor($admin, 'Alerta propia');
        $this->notificationFor($supervisor, 'Alerta ajena');

        $this->actingAs($admin)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Alerta propia')
            ->assertDontSee('Alerta ajena');

        $this->assertSame($own->id, InternalNotification::query()->where('user_id', $admin->id)->value('id'));
    }

    public function test_other_tenant_notification_returns_404(): void
    {
        $farmA = FishFarm::factory()->create();
        $farmB = FishFarm::factory()->create();
        $userA = User::factory()->create(['fish_farm_id' => $farmA->id]);
        $userB = User::factory()->create(['fish_farm_id' => $farmB->id]);
        $foreign = $this->notificationFor($userB, 'Otra cuenta');

        $this->actingAs($userA)
            ->post(route('notifications.read', $foreign))
            ->assertNotFound();

        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_same_tenant_other_users_notification_returns_404(): void
    {
        $farm = FishFarm::factory()->create();
        $admin = User::factory()->create([
            'fish_farm_id' => $farm->id,
            'role' => User::ROLE_ADMIN,
        ]);
        $supervisor = User::factory()->create([
            'fish_farm_id' => $farm->id,
            'role' => User::ROLE_SUPERVISOR,
        ]);
        $other = $this->notificationFor($supervisor, 'Del supervisor');

        $this->actingAs($admin)
            ->post(route('notifications.read', $other))
            ->assertNotFound();

        $this->assertNull($other->fresh()->read_at);
    }

    public function test_notification_can_be_marked_as_read(): void
    {
        $user = User::factory()->create();
        $notification = $this->notificationFor($user, 'Marcar leída');

        $this->actingAs($user)
            ->post(route('notifications.read', $notification))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_marking_read_does_not_delete_notification_or_source(): void
    {
        $user = User::factory()->create();
        $alert = $this->createAlert($user->fishFarm, $user);
        $notification = $this->notificationFor($user, 'Conservar origen', [
            'source_type' => InternalNotification::SOURCE_ALERT,
            'source_id' => $alert->id,
        ]);

        $this->actingAs($user)
            ->post(route('notifications.read', $notification))
            ->assertRedirect();

        $this->assertDatabaseHas('internal_notifications', [
            'id' => $notification->id,
        ]);
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'active',
        ]);
    }

    public function test_related_alert_notification_links_to_pond(): void
    {
        $user = User::factory()->create();
        $alert = $this->createAlert($user->fishFarm, $user);
        $this->notificationFor($user, 'Ver estanque', [
            'source_type' => InternalNotification::SOURCE_ALERT,
            'source_id' => $alert->id,
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(route('ponds.show', $alert->pond_id), false);
    }

    public function test_related_incident_notification_links_to_incident(): void
    {
        $user = User::factory()->create();
        $alert = $this->createAlert($user->fishFarm, $user);
        $incident = Incident::query()->create([
            'alert_id' => $alert->id,
            'fish_farm_id' => $user->fish_farm_id,
            'created_by' => $user->id,
            'title' => 'Revisar temperatura del estanque',
            'description' => 'Requiere seguimiento operativo.',
            'status' => Incident::STATUS_OPEN,
        ]);
        $this->notificationFor($user, 'Ver incidencia', [
            'type' => InternalNotification::TYPE_INCIDENT_ASSIGNED,
            'source_type' => InternalNotification::SOURCE_INCIDENT,
            'source_id' => $incident->id,
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(route('incidents.show', $incident), false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function notificationFor(User $user, string $title, array $overrides = []): InternalNotification
    {
        return InternalNotification::query()->create(array_merge([
            'fish_farm_id' => $user->fish_farm_id,
            'user_id' => $user->id,
            'type' => InternalNotification::TYPE_ALERT_CREATED,
            'source_type' => InternalNotification::SOURCE_ALERT,
            'source_id' => 1,
            'title' => $title,
            'message' => $title.' en Estanque Norte.',
            'read_at' => null,
        ], $overrides));
    }

    private function createAlert(FishFarm $fishFarm, User $creator): Alert
    {
        $pond = $fishFarm->ponds()->create([
            'user_id' => $creator->id,
            'name' => 'Estanque Norte',
            'code' => 'EST-NTF-'.$creator->id,
        ]);
        $device = $pond->devices()->create([
            'name' => 'ESP32 notificación',
            'device_uid' => 'ESP32-NTF-'.$creator->id,
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
