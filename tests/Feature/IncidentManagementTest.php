<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\FishFarm;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_create_incident_from_alert(): void
    {
        [$farm, $supervisor] = $this->farmWithRoles();
        $alert = $this->createAlert($farm, $supervisor);

        $response = $this->actingAs($supervisor)->post(route('incidents.store', $alert), [
            'title' => 'pH fuera de rango en estanque 01',
            'description' => 'Se detectó un descenso de pH que requiere intervención.',
        ]);

        $incident = Incident::query()->firstOrFail();
        $response->assertRedirect(route('incidents.show', $incident));
        $this->assertDatabaseHas('incidents', [
            'alert_id' => $alert->id,
            'fish_farm_id' => $farm->id,
            'created_by' => $supervisor->id,
            'title' => 'pH fuera de rango en estanque 01',
            'status' => Incident::STATUS_OPEN,
        ]);
        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_account_incidents(): void
    {
        [$farm, $supervisor, $admin] = $this->farmWithRoles();
        $alert = $this->createAlert($farm, $supervisor);
        $incident = $this->createIncident($alert, $supervisor);

        $this->actingAs($admin)
            ->get(route('incidents.index'))
            ->assertOk()
            ->assertSee($incident->title);
    }

    public function test_specialist_only_sees_assigned_incidents(): void
    {
        [$farm, $supervisor, , $assigned, $other] = $this->farmWithRoles();
        $assignedIncident = $this->createIncident(
            $this->createAlert($farm, $supervisor, 'EST-ASG'),
            $supervisor,
            ['assigned_to' => $assigned->id, 'status' => Incident::STATUS_ASSIGNED],
        );
        $hiddenIncident = $this->createIncident(
            $this->createAlert($farm, $supervisor, 'EST-HID'),
            $supervisor,
            ['title' => 'Incidencia no asignada al especialista'],
        );

        $this->actingAs($assigned)
            ->get(route('incidents.index'))
            ->assertOk()
            ->assertSee($assignedIncident->title)
            ->assertDontSee($hiddenIncident->title);

        $this->actingAs($other)
            ->get(route('incidents.show', $assignedIncident))
            ->assertNotFound();
    }

    public function test_cannot_access_other_fish_farm_incident(): void
    {
        [$farmA, $supervisorA] = $this->farmWithRoles('a@farm.test');
        [$farmB, $supervisorB] = $this->farmWithRoles('b@farm.test');
        $incident = $this->createIncident($this->createAlert($farmB, $supervisorB), $supervisorB);

        $this->actingAs($supervisorA)
            ->get(route('incidents.show', $incident))
            ->assertNotFound();

        $this->actingAs($supervisorA)
            ->post(route('incidents.assign', $incident), [
                'specialist_id' => User::factory()->create([
                    'fish_farm_id' => $farmA->id,
                    'role' => User::ROLE_SPECIALIST,
                ])->id,
            ])
            ->assertNotFound();
    }

    public function test_alert_can_have_only_one_incident(): void
    {
        [$farm, $supervisor] = $this->farmWithRoles();
        $alert = $this->createAlert($farm, $supervisor);
        $this->createIncident($alert, $supervisor);

        $this->actingAs($supervisor)
            ->post(route('incidents.store', $alert), [
                'title' => 'Segunda incidencia',
                'description' => 'No debe crearse porque la alerta ya tiene una incidencia.',
            ])
            ->assertSessionHasErrors('alert_id');

        $this->assertSame(1, Incident::query()->where('alert_id', $alert->id)->count());
    }

    public function test_specialist_can_start_and_resolve_assigned_incident(): void
    {
        [$farm, $supervisor, , $specialist] = $this->farmWithRoles();
        $incident = $this->createIncident(
            $this->createAlert($farm, $supervisor),
            $supervisor,
            ['assigned_to' => $specialist->id, 'status' => Incident::STATUS_ASSIGNED],
        );

        $this->actingAs($specialist)
            ->post(route('incidents.start', $incident))
            ->assertRedirect(route('incidents.show', $incident));

        $this->assertSame(Incident::STATUS_IN_PROGRESS, $incident->fresh()->status);

        $this->actingAs($specialist)
            ->post(route('incidents.resolve', $incident), [
                'resolution' => 'Se realizó recambio parcial y se verificó el pH.',
            ])
            ->assertRedirect(route('incidents.show', $incident));

        $incident->refresh();
        $this->assertSame(Incident::STATUS_RESOLVED, $incident->status);
        $this->assertSame('Se realizó recambio parcial y se verificó el pH.', $incident->resolution);
        $this->assertNotNull($incident->resolved_at);
        $this->assertDatabaseHas('alerts', [
            'id' => $incident->alert_id,
        ]);
    }

    public function test_admin_can_assign_specialist_and_close_incident(): void
    {
        [$farm, $supervisor, $admin, $specialist] = $this->farmWithRoles();
        $incident = $this->createIncident($this->createAlert($farm, $supervisor), $supervisor);

        $this->actingAs($admin)
            ->post(route('incidents.assign', $incident), [
                'specialist_id' => $specialist->id,
            ])
            ->assertRedirect(route('incidents.show', $incident));

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'assigned_to' => $specialist->id,
            'status' => Incident::STATUS_ASSIGNED,
        ]);

        $incident->update([
            'status' => Incident::STATUS_RESOLVED,
            'resolution' => 'Corrección aplicada.',
            'resolved_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('incidents.close', $incident))
            ->assertRedirect(route('incidents.show', $incident));

        $this->assertSame(Incident::STATUS_CLOSED, $incident->fresh()->status);
    }

    public function test_specialist_cannot_create_incident(): void
    {
        [$farm, $supervisor, , $specialist] = $this->farmWithRoles();
        $alert = $this->createAlert($farm, $supervisor);

        $this->actingAs($specialist)
            ->post(route('incidents.store', $alert), [
                'title' => 'No autorizado',
                'description' => 'El especialista no crea incidencias.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_supervisor_cannot_close_incident(): void
    {
        [$farm, $supervisor] = $this->farmWithRoles();
        $incident = $this->createIncident(
            $this->createAlert($farm, $supervisor),
            $supervisor,
            [
                'status' => Incident::STATUS_RESOLVED,
                'resolution' => 'Trabajo documentado.',
                'resolved_at' => now(),
            ],
        );

        $this->actingAs($supervisor)
            ->post(route('incidents.close', $incident))
            ->assertForbidden();

        $this->assertSame(Incident::STATUS_RESOLVED, $incident->fresh()->status);
    }

    /**
     * @return array{0: FishFarm, 1: User, 2: User, 3: User, 4: User}
     */
    private function farmWithRoles(string $suffix = 'farm@aqualytics.test'): array
    {
        $farm = FishFarm::factory()->create([
            'name' => 'Granja '.$suffix,
        ]);
        $supervisor = User::factory()->create([
            'fish_farm_id' => $farm->id,
            'email' => 'supervisor-'.$suffix,
            'role' => User::ROLE_SUPERVISOR,
        ]);
        $admin = User::factory()->create([
            'fish_farm_id' => $farm->id,
            'email' => 'admin-'.$suffix,
            'role' => User::ROLE_ADMIN,
        ]);
        $assigned = User::factory()->create([
            'fish_farm_id' => $farm->id,
            'email' => 'asig-'.$suffix,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $other = User::factory()->create([
            'fish_farm_id' => $farm->id,
            'email' => 'otro-'.$suffix,
            'role' => User::ROLE_SPECIALIST,
        ]);

        return [$farm, $supervisor, $admin, $assigned, $other];
    }

    private function createAlert(FishFarm $farm, User $creator, string $code = 'EST-INC'): Alert
    {
        $pond = $farm->ponds()->create([
            'user_id' => $creator->id,
            'name' => 'Estanque incidencia',
            'code' => $code.'-'.$creator->id,
        ]);
        $device = $pond->devices()->create([
            'name' => 'ESP32 incidencia',
            'device_uid' => 'INC-'.$code.'-'.$creator->id,
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createIncident(Alert $alert, User $creator, array $overrides = []): Incident
    {
        return Incident::query()->create(array_merge([
            'alert_id' => $alert->id,
            'fish_farm_id' => $alert->pond->fish_farm_id,
            'created_by' => $creator->id,
            'title' => 'Incidencia de pH',
            'description' => 'Requiere seguimiento operativo.',
            'status' => Incident::STATUS_OPEN,
        ], $overrides));
    }
}
