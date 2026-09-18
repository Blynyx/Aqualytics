<?php

namespace App\Http\Controllers;

use App\Models\Pond;
use App\Models\User;
use App\Services\ReadingHistoryService;
use App\Services\SubscriptionLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PondController extends Controller
{
    public function index(Request $request): View
    {
        $ponds = $request->user()
            ->fishFarm
            ->ponds()
            ->orderBy('name')
            ->get();

        return view('ponds.index', compact('ponds'));
    }

    public function create(): View
    {
        return view('ponds.create');
    }

    public function show(Request $request, Pond $pond): View
    {
        abort_unless(
            $pond->fish_farm_id === $request->user()->fish_farm_id,
            404,
        );

        $pond->load([
            'devices' => fn ($query) => $query->orderBy('name'),
            'threshold',
        ]);

        $latestReadings = $pond->readings()
            ->with('device')
            ->latest('recorded_at')
            ->limit(10)
            ->get();

        $activeAlerts = $pond->alerts()
            ->with(['assignedTo', 'incident'])
            ->whereIn('status', ['active', 'assigned'])
            ->latest('detected_at')
            ->get();

        $resolvedAlerts = $pond->alerts()
            ->with(['assignedTo', 'resolvedBy'])
            ->where('status', 'resolved')
            ->latest('resolved_at')
            ->limit(10)
            ->get();

        $specialists = $request->user()
            ->fishFarm
            ->users()
            ->where('role', User::ROLE_SPECIALIST)
            ->orderBy('name')
            ->get();

        $history = app(ReadingHistoryService::class);
        $allowedHistoryRanges = $history->allowedRanges($request->user()->fishFarm);

        return view('ponds.show', [
            'pond' => $pond,
            'latestReading' => $latestReadings->first(),
            'latestReadings' => $latestReadings,
            'activeAlerts' => $activeAlerts,
            'resolvedAlerts' => $resolvedAlerts,
            'specialists' => $specialists,
            'allowedHistoryRanges' => $allowedHistoryRanges,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $fishFarm = $request->user()->fishFarm;
        $limits = app(SubscriptionLimitService::class);

        if (! $limits->canCreateUnit($fishFarm)) {
            throw ValidationException::withMessages([
                'name' => 'Has alcanzado el límite de '.$fishFarm->unitsLabel().' de tu plan.',
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ponds', 'code')->where(
                    fn ($query) => $query->where('fish_farm_id', $fishFarm->id)
                ),
            ],
            'species' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $pond = $fishFarm->ponds()->create([
            ...$validated,
            'user_id' => $request->user()->id,
            'status' => 'active',
            'unit_type' => $fishFarm->isHome() ? Pond::TYPE_AQUARIUM : Pond::TYPE_POND,
        ]);

        return redirect("/ponds/{$pond->id}");
    }
}
