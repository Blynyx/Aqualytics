<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Device;
use App\Models\FishFarm;
use App\Models\Incident;
use App\Models\PondThreshold;
use App\Models\Reading;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardDataService
{
    public const RECENT_READINGS_LIMIT = 10;

    public const RECENT_ALERTS_LIMIT = 10;

    public const RECENT_INCIDENTS_LIMIT = 10;

    public const PONDS_SUMMARY_LIMIT = 12;

    public const CONTEXT_HOME = 'home';

    public const CONTEXT_FARM_ADMIN = 'farm_admin';

    public const CONTEXT_FARM_SUPERVISOR = 'farm_supervisor';

    public const CONTEXT_FARM_SPECIALIST = 'farm_specialist';

    public function __construct(
        private SubscriptionLimitService $subscriptionLimitService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $user->loadMissing('fishFarm.subscription.plan');

        return match ($this->contextFor($user)) {
            self::CONTEXT_HOME => $this->forHome($user),
            self::CONTEXT_FARM_ADMIN => $this->forFarmAdmin($user),
            self::CONTEXT_FARM_SUPERVISOR => $this->forFarmSupervisor($user),
            default => $this->forFarmSpecialist($user),
        };
    }

    public function contextFor(User $user): string
    {
        $fishFarm = $user->fishFarm;

        if ($fishFarm !== null && $fishFarm->isHome()) {
            return self::CONTEXT_HOME;
        }

        return match ($user->role) {
            User::ROLE_ADMIN => self::CONTEXT_FARM_ADMIN,
            User::ROLE_SUPERVISOR => self::CONTEXT_FARM_SUPERVISOR,
            default => self::CONTEXT_FARM_SPECIALIST,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function forHome(User $user): array
    {
        $fishFarm = $user->fishFarm;
        $pondIds = $this->pondIds($fishFarm);
        $pond = $fishFarm->ponds()->orderBy('id')->first();
        $device = $pond?->devices()->orderBy('id')->first();
        $latestReading = $pond === null
            ? null
            : $pond->readings()->latest('recorded_at')->first();
        $threshold = $pond?->threshold;

        return [
            'dashboardContext' => self::CONTEXT_HOME,
            'account' => $fishFarm,
            'plan' => $fishFarm->subscription?->plan,
            'usage' => $this->subscriptionLimitService->usage($fishFarm),
            'pond' => $pond,
            'device' => $device,
            'latestReading' => $latestReading,
            'threshold' => $threshold,
            'parameterStates' => $this->parameterStates($latestReading, $threshold),
            'activeAlertCount' => $this->alertCount($pondIds, 'active'),
            'activeAlerts' => $this->recentAlerts($pondIds, 'active'),
            'historyUrl' => $pond === null
                ? null
                : route('ponds.readings.history', $pond),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function forFarmAdmin(User $user): array
    {
        $fishFarm = $user->fishFarm;
        $pondIds = $this->pondIds($fishFarm);

        return [
            'dashboardContext' => self::CONTEXT_FARM_ADMIN,
            'account' => $fishFarm,
            'plan' => $fishFarm->subscription?->plan,
            'usage' => $this->subscriptionLimitService->usage($fishFarm),
            'pondCount' => $fishFarm->ponds()->count(),
            'deviceCount' => $this->deviceCount($pondIds),
            'userCount' => $fishFarm->users()->count(),
            'readingCount' => $this->readingCount($pondIds),
            'activeAlertCount' => $this->alertCount($pondIds, 'active'),
            'pendingIncidentCount' => Incident::query()
                ->where('fish_farm_id', $fishFarm->id)
                ->whereIn('status', [
                    Incident::STATUS_OPEN,
                    Incident::STATUS_ASSIGNED,
                    Incident::STATUS_IN_PROGRESS,
                ])
                ->count(),
            'ponds' => $fishFarm->ponds()->orderBy('name')->limit(self::PONDS_SUMMARY_LIMIT)->get(),
            'latestReadings' => $this->recentReadings($pondIds),
            'recentActiveAlerts' => $this->recentAlerts($pondIds, 'active'),
            'pendingIncidents' => Incident::query()
                ->with(['alert.pond', 'assignee'])
                ->where('fish_farm_id', $fishFarm->id)
                ->whereIn('status', [
                    Incident::STATUS_OPEN,
                    Incident::STATUS_ASSIGNED,
                    Incident::STATUS_IN_PROGRESS,
                ])
                ->latest()
                ->limit(self::RECENT_INCIDENTS_LIMIT)
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function forFarmSupervisor(User $user): array
    {
        $fishFarm = $user->fishFarm;
        $pondIds = $this->pondIds($fishFarm);
        $operational = [
            Incident::STATUS_OPEN,
            Incident::STATUS_ASSIGNED,
            Incident::STATUS_IN_PROGRESS,
        ];

        return [
            'dashboardContext' => self::CONTEXT_FARM_SUPERVISOR,
            'account' => $fishFarm,
            'activeAlertCount' => $this->alertCount($pondIds, 'active'),
            'assignedAlertCount' => $this->alertCount($pondIds, 'assigned'),
            'openIncidentCount' => $this->incidentCount($fishFarm->id, Incident::STATUS_OPEN),
            'assignedIncidentCount' => $this->incidentCount($fishFarm->id, Incident::STATUS_ASSIGNED),
            'inProgressIncidentCount' => $this->incidentCount($fishFarm->id, Incident::STATUS_IN_PROGRESS),
            'pondsWithActiveAlertsCount' => $this->pondsWithActiveAlertsCount($pondIds),
            'latestRelevantReadings' => $this->recentReadings($pondIds),
            'activeAlerts' => $this->recentAlerts($pondIds, 'active'),
            'operationalIncidents' => Incident::query()
                ->with(['alert.pond', 'assignee'])
                ->where('fish_farm_id', $fishFarm->id)
                ->whereIn('status', $operational)
                ->latest()
                ->limit(self::RECENT_INCIDENTS_LIMIT)
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function forFarmSpecialist(User $user): array
    {
        $fishFarm = $user->fishFarm;
        $myActiveIncidents = $this->specialistIncidentsQuery($user)
            ->with(['alert.pond', 'assignee'])
            ->whereIn('status', [Incident::STATUS_ASSIGNED, Incident::STATUS_IN_PROGRESS])
            ->latest()
            ->limit(self::RECENT_INCIDENTS_LIMIT)
            ->get();

        $recentResolvedIncidents = $this->specialistIncidentsQuery($user)
            ->with(['alert.pond', 'assignee'])
            ->where('status', Incident::STATUS_RESOLVED)
            ->latest('resolved_at')
            ->limit(self::RECENT_INCIDENTS_LIMIT)
            ->get();

        $relatedPonds = $myActiveIncidents
            ->concat($recentResolvedIncidents)
            ->map(fn (Incident $incident): mixed => $incident->alert?->pond)
            ->filter()
            ->unique('id')
            ->values();

        return [
            'dashboardContext' => self::CONTEXT_FARM_SPECIALIST,
            'account' => $fishFarm,
            'assignedIncidentCount' => $this->specialistIncidentsQuery($user)
                ->where('status', Incident::STATUS_ASSIGNED)
                ->count(),
            'inProgressIncidentCount' => $this->specialistIncidentsQuery($user)
                ->where('status', Incident::STATUS_IN_PROGRESS)
                ->count(),
            'myActiveIncidents' => $myActiveIncidents,
            'recentResolvedIncidents' => $recentResolvedIncidents,
            'relatedPonds' => $relatedPonds,
        ];
    }

    private function specialistIncidentsQuery(User $user)
    {
        return Incident::query()
            ->where('fish_farm_id', $user->fish_farm_id)
            ->where('assigned_to', $user->id);
    }

    /**
     * @param  list<int>  $pondIds
     */
    private function pondsWithActiveAlertsCount(array $pondIds): int
    {
        if ($pondIds === []) {
            return 0;
        }

        return Alert::query()
            ->whereIn('pond_id', $pondIds)
            ->where('status', 'active')
            ->distinct()
            ->count('pond_id');
    }

    /**
     * @return list<int>
     */
    private function pondIds(FishFarm $fishFarm): array
    {
        return $fishFarm->ponds()->pluck('id')->all();
    }

    /**
     * @param  list<int>  $pondIds
     */
    private function deviceCount(array $pondIds): int
    {
        if ($pondIds === []) {
            return 0;
        }

        return Device::query()->whereIn('pond_id', $pondIds)->count();
    }

    /**
     * @param  list<int>  $pondIds
     */
    private function readingCount(array $pondIds): int
    {
        if ($pondIds === []) {
            return 0;
        }

        return Reading::query()->whereIn('pond_id', $pondIds)->count();
    }

    /**
     * @param  list<int>  $pondIds
     */
    private function alertCount(array $pondIds, string $status): int
    {
        if ($pondIds === []) {
            return 0;
        }

        return Alert::query()
            ->whereIn('pond_id', $pondIds)
            ->where('status', $status)
            ->count();
    }

    private function incidentCount(int $fishFarmId, string $status): int
    {
        return Incident::query()
            ->where('fish_farm_id', $fishFarmId)
            ->where('status', $status)
            ->count();
    }

    /**
     * @param  list<int>  $pondIds
     * @return Collection<int, Reading>
     */
    private function recentReadings(array $pondIds): Collection
    {
        if ($pondIds === []) {
            return collect();
        }

        return Reading::query()
            ->with(['pond', 'device'])
            ->whereIn('pond_id', $pondIds)
            ->latest('recorded_at')
            ->limit(self::RECENT_READINGS_LIMIT)
            ->get();
    }

    /**
     * @param  list<int>  $pondIds
     * @return Collection<int, Alert>
     */
    private function recentAlerts(array $pondIds, string $status): Collection
    {
        if ($pondIds === []) {
            return collect();
        }

        return Alert::query()
            ->with('pond')
            ->whereIn('pond_id', $pondIds)
            ->where('status', $status)
            ->latest('detected_at')
            ->limit(self::RECENT_ALERTS_LIMIT)
            ->get();
    }

    /**
     * @return array{temperature: string, ph: string, turbidity: string, water_level: string}
     */
    private function parameterStates(?Reading $reading, ?PondThreshold $threshold): array
    {
        return [
            'temperature' => $this->rangeState(
                $reading?->temperature,
                $threshold?->temperature_min,
                $threshold?->temperature_max,
            ),
            'ph' => $this->rangeState(
                $reading?->ph,
                $threshold?->ph_min,
                $threshold?->ph_max,
            ),
            'turbidity' => $this->rangeState(
                $reading?->turbidity,
                null,
                $threshold?->turbidity_max,
            ),
            'water_level' => $this->rangeState(
                $reading?->water_level,
                $threshold?->water_level_min,
                $threshold?->water_level_max,
            ),
        ];
    }

    private function rangeState(mixed $value, mixed $min, mixed $max): string
    {
        if ($value === null || ($min === null && $max === null)) {
            return 'unevaluated';
        }

        if ($min !== null && $value < $min) {
            return 'out_of_range';
        }

        if ($max !== null && $value > $max) {
            return 'out_of_range';
        }

        return 'ok';
    }
}
