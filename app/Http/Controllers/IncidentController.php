<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Incident;
use App\Models\User;
use App\Services\InternalNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IncidentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $incidents = Incident::query()
            ->with(['alert.pond', 'assignee', 'creator'])
            ->where('fish_farm_id', $user->fish_farm_id)
            ->when(
                $user->role === User::ROLE_SPECIALIST,
                fn ($query) => $query->where('assigned_to', $user->id),
            )
            ->latest()
            ->get();

        return view('incidents.index', compact('incidents'));
    }

    public function show(Request $request, Incident $incident): View
    {
        $this->ensureVisible($request, $incident);

        $incident->load(['alert.pond', 'alert.device', 'assignee', 'creator']);

        $specialists = $request->user()
            ->fishFarm
            ->users()
            ->where('role', User::ROLE_SPECIALIST)
            ->orderBy('name')
            ->get();

        return view('incidents.show', compact('incident', 'specialists'));
    }

    public function store(Request $request, Alert $alert): RedirectResponse
    {
        $this->ensureSameFarm($request, $alert->pond->fish_farm_id);
        $this->ensureCanManage($request);

        if ($alert->incident()->exists()) {
            throw ValidationException::withMessages([
                'alert_id' => 'Esta alerta ya tiene una incidencia registrada.',
            ]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $incident = Incident::query()->create([
            'alert_id' => $alert->id,
            'fish_farm_id' => $alert->pond->fish_farm_id,
            'created_by' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => Incident::STATUS_OPEN,
        ]);

        return redirect()->route('incidents.show', $incident);
    }

    public function assign(
        Request $request,
        Incident $incident,
        InternalNotificationService $internalNotificationService,
    ): RedirectResponse {
        $this->ensureSameFarm($request, $incident->fish_farm_id);
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'specialist_id' => [
                'required',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query
                        ->where('fish_farm_id', $request->user()->fish_farm_id)
                        ->where('role', User::ROLE_SPECIALIST),
                ),
            ],
        ]);

        $specialist = User::query()->findOrFail($validated['specialist_id']);

        $incident->update([
            'assigned_to' => $specialist->id,
            'status' => Incident::STATUS_ASSIGNED,
        ]);

        $internalNotificationService->notifyIncidentAssigned($incident, $specialist);

        return redirect()->route('incidents.show', $incident);
    }

    public function start(Request $request, Incident $incident): RedirectResponse
    {
        $this->ensureVisible($request, $incident);
        abort_unless(
            $request->user()->role === User::ROLE_SPECIALIST
            && $incident->isAssignedTo($request->user()),
            403,
        );

        $incident->update([
            'status' => Incident::STATUS_IN_PROGRESS,
        ]);

        return redirect()->route('incidents.show', $incident);
    }

    public function resolve(Request $request, Incident $incident): RedirectResponse
    {
        $this->ensureVisible($request, $incident);
        abort_unless(
            $request->user()->role === User::ROLE_SPECIALIST
            && $incident->isAssignedTo($request->user()),
            403,
        );

        $validated = $request->validate([
            'resolution' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $incident->update([
            'status' => Incident::STATUS_RESOLVED,
            'resolution' => $validated['resolution'],
            'resolved_at' => now(),
        ]);

        return redirect()->route('incidents.show', $incident);
    }

    public function close(Request $request, Incident $incident): RedirectResponse
    {
        $this->ensureSameFarm($request, $incident->fish_farm_id);
        abort_unless($request->user()->role === User::ROLE_ADMIN, 403);

        $incident->update([
            'status' => Incident::STATUS_CLOSED,
        ]);

        return redirect()->route('incidents.show', $incident);
    }

    private function ensureVisible(Request $request, Incident $incident): void
    {
        $this->ensureSameFarm($request, $incident->fish_farm_id);

        if ($request->user()->role === User::ROLE_SPECIALIST) {
            abort_unless($incident->isAssignedTo($request->user()), 404);
        }
    }

    private function ensureSameFarm(Request $request, ?int $fishFarmId): void
    {
        abort_unless($fishFarmId === $request->user()->fish_farm_id, 404);
    }

    private function ensureCanManage(Request $request): void
    {
        abort_unless(
            in_array($request->user()->role, [
                User::ROLE_ADMIN,
                User::ROLE_SUPERVISOR,
            ], true),
            403,
        );
    }
}
