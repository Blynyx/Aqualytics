<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\FishFarm;
use App\Models\Incident;
use App\Models\InternalNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardByRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_dashboard_shows_home_context(): void
    {
        $admin = $this->homeAdmin();
        $this->pondOn($admin->fishFarm, $admin, 'Pecera Casa');

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-dashboard-context="home"', false)
            ->assertSee('Pecera Casa');
    }

    public function test_home_dashboard_handles_missing_pond(): void
    {
        $admin = $this->homeAdmin();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-dashboard-context="home"', false)
            ->assertSee('data-cy="dashboard-empty-state"', false);
    }

    public function test_home_dashboard_handles_missing_reading(): void
    {
        $admin = $this->homeAdmin();
        $this->pondOn($admin->fishFarm, $admin, 'Pecera Sin Lecturas');

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Pecera Sin Lecturas')
            ->assertSee('data-cy="dashboard-empty-state"', false);
    }

    public function test_home_dashboard_does_not_show_farm_team_blocks(): void
    {
        $admin = $this->homeAdmin();
        $this->pondOn($admin->fishFarm, $admin, 'Pecera Casa');

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('data-cy="dashboard-my-incidents"', false)
            ->assertDontSee('data-cy="dashboard-pending-incidents"', false)
            ->assertDontSee('Mis incidencias')
            ->assertDontSee('Incidencias pendientes');
    }

    public function test_farm_admin_dashboard_shows_own_farm_aggregates(): void
    {
        $farm = $this->farmWithRoles();
        $pondA = $this->pondOn($farm['farm'], $farm['admin'], 'Estanque Alfa', 'EST-ALF');
        $this->pondOn($farm['farm'], $farm['admin'], 'Estanque Beta', 'EST-BET');
        $this->deviceOn($pondA, 'ESP32-ALF');
        $this->createIncident($farm['farm'], $farm['admin'], $pondA, 'Incidencia pendiente Alfa', Incident::STATUS_OPEN);

        $this->actingAs($farm['admin'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-dashboard-context="farm_admin"', false)
            ->assertSee('Estanque Alfa')
            ->assertSee('Estanque Beta')
            ->assertSee('data-cy="dashboard-pending-incidents"', false)
            ->assertSee('Incidencia pendiente Alfa');
    }

    public function test_farm_admin_dashboard_ignores_other_farm_data(): void
    {
        $farmA = $this->farmWithRoles('a@farm.test');
        $farmB = $this->farmWithRoles('b@farm.test');
        $this->pondOn($farmA['farm'], $farmA['admin'], 'Estanque Propio A', 'EST-A1');
        $pondB = $this->pondOn($farmB['farm'], $farmB['admin'], 'Estanque Ajeno B', 'EST-B1');
        $this->createIncident($farmB['farm'], $farmB['admin'], $pondB, 'Incidencia secreta de B', Incident::STATUS_OPEN);

        $this->actingAs($farmA['admin'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Estanque Propio A')
            ->assertDontSee('Estanque Ajeno B')
            ->assertDontSee('Incidencia secreta de B');
    }

    public function test_farm_supervisor_dashboard_shows_operational_metrics(): void
    {
        $farm = $this->farmWithRoles();
        $pond = $this->pondOn($farm['farm'], $farm['admin'], 'Estanque Operativo');
        $this->createAlert($pond, $farm['admin'], 'Alerta activa operativa', 'active');
        $this->createAlert($pond, $farm['admin'], 'Alerta asignada operativa', 'assigned');
        $this->createIncident($farm['farm'], $farm['supervisor'], $pond, 'Incidencia abierta operativa', Incident::STATUS_OPEN);

        $this->actingAs($farm['supervisor'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-dashboard-context="farm_supervisor"', false)
            ->assertSee('Alerta activa operativa')
            ->assertSee('Incidencia abierta operativa')
            ->assertSee('data-cy="dashboard-active-alerts"', false)
            ->assertDontSee('data-cy="dashboard-user-management"', false);
    }

    public function test_farm_supervisor_dashboard_ignores_other_farm_data(): void
    {
        $farmA = $this->farmWithRoles('sa@farm.test');
        $farmB = $this->farmWithRoles('sb@farm.test');
        $pondA = $this->pondOn($farmA['farm'], $farmA['admin'], 'Estanque Super A', 'SUP-A');
        $pondB = $this->pondOn($farmB['farm'], $farmB['admin'], 'Estanque Super B', 'SUP-B');
        $this->createIncident($farmA['farm'], $farmA['supervisor'], $pondA, 'Incidencia supervisor A', Incident::STATUS_OPEN);
        $this->createIncident($farmB['farm'], $farmB['supervisor'], $pondB, 'Incidencia supervisor B', Incident::STATUS_OPEN);

        $this->actingAs($farmA['supervisor'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Incidencia supervisor A')
            ->assertDontSee('Incidencia supervisor B')
            ->assertDontSee('Estanque Super B');
    }

    public function test_farm_specialist_dashboard_shows_only_assigned_work(): void
    {
        $farm = $this->farmWithRoles();
        $pond = $this->pondOn($farm['farm'], $farm['admin'], 'Estanque Especialista');
        $this->createIncident(
            $farm['farm'],
            $farm['supervisor'],
            $pond,
            'Incidencia mía asignada',
            Incident::STATUS_ASSIGNED,
            $farm['specialist']->id,
        );
        $this->createIncident(
            $farm['farm'],
            $farm['supervisor'],
            $pond,
            'Incidencia abierta del equipo',
            Incident::STATUS_OPEN,
        );

        $this->actingAs($farm['specialist'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-dashboard-context="farm_specialist"', false)
            ->assertSee('data-cy="dashboard-my-incidents"', false)
            ->assertSee('Incidencia mía asignada')
            ->assertDontSee('Incidencia abierta del equipo');
    }

    public function test_farm_specialist_does_not_include_other_specialists_incidents(): void
    {
        $farm = $this->farmWithRoles();
        $other = User::factory()->create([
            'fish_farm_id' => $farm['farm']->id,
            'role' => User::ROLE_SPECIALIST,
        ]);
        $pond = $this->pondOn($farm['farm'], $farm['admin'], 'Estanque Compartido');
        $this->createIncident(
            $farm['farm'],
            $farm['supervisor'],
            $pond,
            'Trabajo del especialista A',
            Incident::STATUS_ASSIGNED,
            $farm['specialist']->id,
        );
        $this->createIncident(
            $farm['farm'],
            $farm['supervisor'],
            $pond,
            'Trabajo del especialista B',
            Incident::STATUS_ASSIGNED,
            $other->id,
        );

        $this->actingAs($farm['specialist'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Trabajo del especialista A')
            ->assertDontSee('Trabajo del especialista B');
    }

    public function test_dashboard_handles_zero_alerts(): void
    {
        $farm = $this->farmWithRoles();
        $this->pondOn($farm['farm'], $farm['admin'], 'Estanque Sin Alertas');

        $this->actingAs($farm['admin'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-cy="dashboard-empty-state"', false);
    }

    public function test_dashboard_handles_zero_incidents(): void
    {
        $farm = $this->farmWithRoles();
        $this->pondOn($farm['farm'], $farm['admin'], 'Estanque Sin Incidencias');

        $this->actingAs($farm['supervisor'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-dashboard-context="farm_supervisor"', false)
            ->assertSee('data-cy="dashboard-empty-state"', false);
    }

    public function test_dashboard_unread_count_matches_authenticated_user(): void
    {
        $farm = $this->farmWithRoles();
        $this->notificationFor($farm['admin'], 'Aviso admin uno');
        $this->notificationFor($farm['admin'], 'Aviso admin dos');
        $this->notificationFor($farm['supervisor'], 'Aviso supervisor ajeno');

        $this->actingAs($farm['admin'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-cy="notification-unread-count"', false)
            ->assertSee('>2</span>', false)
            ->assertDontSee('Aviso supervisor ajeno');
    }

    public function test_dashboard_request_does_not_change_domain_state(): void
    {
        $farm = $this->farmWithRoles();
        $pond = $this->pondOn($farm['farm'], $farm['admin'], 'Estanque Inmutable');
        $alert = $this->createAlert($pond, $farm['admin'], 'Alerta inmutable', 'active');
        $incident = $this->createIncident(
            $farm['farm'],
            $farm['supervisor'],
            $pond,
            'Incidencia inmutable',
            Incident::STATUS_OPEN,
        );

        $this->actingAs($farm['admin'])->get('/dashboard')->assertOk();

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'status' => Incident::STATUS_OPEN,
        ]);
    }

    public function test_dashboard_lists_are_limited(): void
    {
        $farm = $this->farmWithRoles();
        $pond = $this->pondOn($farm['farm'], $farm['admin'], 'Estanque Límite');
        $device = $this->deviceOn($pond, 'ESP32-LIM');

        for ($i = 1; $i <= 12; $i++) {
            $device->readings()->create([
                'pond_id' => $pond->id,
                'temperature' => 20 + $i,
                'ph' => 7.0,
                'turbidity' => 10,
                'water_level' => 80,
                'recorded_at' => now()->subMinutes(12 - $i),
            ]);
        }

        $html = $this->actingAs($farm['admin'])
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertSame(10, substr_count($html, 'data-cy="dashboard-latest-reading-row"'));
    }

    public function test_specialist_dashboard_does_not_query_per_incident(): void
    {
        $farm = $this->farmWithRoles();
        $pond = $this->pondOn($farm['farm'], $farm['admin'], 'Estanque N+1');

        $this->createIncident(
            $farm['farm'],
            $farm['supervisor'],
            $pond,
            'Incidencia eager 1',
            Incident::STATUS_ASSIGNED,
            $farm['specialist']->id,
        );

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($farm['specialist'])->get('/dashboard')->assertOk();
        $oneIncidentQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        for ($i = 2; $i <= 6; $i++) {
            $this->createIncident(
                $farm['farm'],
                $farm['supervisor'],
                $pond,
                'Incidencia eager '.$i,
                Incident::STATUS_ASSIGNED,
                $farm['specialist']->id,
            );
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($farm['specialist'])->get('/dashboard')->assertOk();
        $manyIncidentQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(
            $oneIncidentQueries + 5,
            $manyIncidentQueries,
            "Queries with 6 incidents ({$manyIncidentQueries}) grew linearly from 1 incident ({$oneIncidentQueries}).",
        );
    }

    public function test_dashboard_query_baseline_can_be_observed(): void
    {
        $home = $this->homeAdmin();
        $this->pondOn($home->fishFarm, $home, 'Pecera Baseline');
        $farm = $this->farmWithRoles('baseline@farm.test');
        $pond = $this->pondOn($farm['farm'], $farm['admin'], 'Estanque Baseline');
        $this->createIncident(
            $farm['farm'],
            $farm['supervisor'],
            $pond,
            'Incidencia baseline',
            Incident::STATUS_ASSIGNED,
            $farm['specialist']->id,
        );

        $baselines = [];

        foreach ([
            'HOME' => $home,
            'ADMIN' => $farm['admin'],
            'SUPERVISOR' => $farm['supervisor'],
            'SPECIALIST' => $farm['specialist'],
        ] as $label => $user) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $started = hrtime(true);
            $this->actingAs($user)->get('/dashboard')->assertOk();
            $elapsedMs = (hrtime(true) - $started) / 1_000_000;
            $baselines[$label] = [
                'queries' => count(DB::getQueryLog()),
                'ms' => round($elapsedMs, 1),
            ];
            DB::disableQueryLog();
        }

        fwrite(STDERR, PHP_EOL.'DASHBOARD_QUERY_BASELINE '.json_encode($baselines).PHP_EOL);

        foreach ($baselines as $label => $stats) {
            $this->assertGreaterThan(0, $stats['queries'], "{$label} dashboard produced no queries.");
        }
    }

    /**
     * @return array{farm: FishFarm, admin: User, supervisor: User, specialist: User}
     */
    private function farmWithRoles(string $suffix = 'farm@aqualytics.test'): array
    {
        $farm = FishFarm::factory()->create([
            'name' => 'Granja '.$suffix,
            'account_type' => FishFarm::TYPE_FARM,
        ]);

        return [
            'farm' => $farm,
            'admin' => User::factory()->create([
                'fish_farm_id' => $farm->id,
                'email' => 'admin-'.$suffix,
                'role' => User::ROLE_ADMIN,
            ]),
            'supervisor' => User::factory()->create([
                'fish_farm_id' => $farm->id,
                'email' => 'supervisor-'.$suffix,
                'role' => User::ROLE_SUPERVISOR,
            ]),
            'specialist' => User::factory()->create([
                'fish_farm_id' => $farm->id,
                'email' => 'specialist-'.$suffix,
                'role' => User::ROLE_SPECIALIST,
            ]),
        ];
    }

    private function homeAdmin(): User
    {
        $account = FishFarm::factory()->create([
            'account_type' => FishFarm::TYPE_HOME,
            'name' => 'Cuenta Home',
        ]);

        return User::factory()->create([
            'fish_farm_id' => $account->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function pondOn(FishFarm $farm, User $owner, string $name, ?string $code = null)
    {
        return $farm->ponds()->create([
            'user_id' => $owner->id,
            'name' => $name,
            'code' => $code ?? 'PND-'.$owner->id.'-'.substr(md5($name), 0, 6),
        ]);
    }

    private function deviceOn($pond, string $uid)
    {
        return $pond->devices()->create([
            'name' => 'Sensor '.$uid,
            'device_uid' => $uid,
            'status' => 'active',
        ]);
    }

    private function createAlert($pond, User $creator, string $message, string $status): Alert
    {
        $device = $pond->devices()->first() ?? $this->deviceOn($pond, 'ESP32-ALR-'.$pond->id.'-'.substr(md5($message), 0, 4));
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
            'status' => $status,
            'message' => $message,
            'detected_at' => now(),
        ]);
    }

    private function createIncident(
        FishFarm $farm,
        User $creator,
        $pond,
        string $title,
        string $status,
        ?int $assignedTo = null,
    ): Incident {
        $alert = $this->createAlert($pond, $creator, 'Alerta de '.$title, 'active');

        return Incident::query()->create([
            'alert_id' => $alert->id,
            'fish_farm_id' => $farm->id,
            'created_by' => $creator->id,
            'assigned_to' => $assignedTo,
            'title' => $title,
            'description' => 'Descripción operativa de la incidencia.',
            'status' => $status,
        ]);
    }

    private function notificationFor(User $user, string $title): InternalNotification
    {
        return InternalNotification::query()->create([
            'fish_farm_id' => $user->fish_farm_id,
            'user_id' => $user->id,
            'type' => InternalNotification::TYPE_ALERT_CREATED,
            'source_type' => InternalNotification::SOURCE_ALERT,
            'source_id' => 1,
            'title' => $title,
            'message' => $title.'.',
        ]);
    }
}
