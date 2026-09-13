<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Device;
use App\Models\Reading;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $ponds = $request->user()
            ->fishFarm
            ->ponds()
            ->orderBy('name')
            ->get();
        $pondIds = $ponds->modelKeys();

        $deviceCount = Device::whereIn('pond_id', $pondIds)->count();
        $readingCount = Reading::whereIn('pond_id', $pondIds)->count();
        $activeAlertCount = Alert::whereIn('pond_id', $pondIds)
            ->where('status', 'active')
            ->count();

        $latestReadings = Reading::with(['pond', 'device'])
            ->whereIn('pond_id', $pondIds)
            ->latest('recorded_at')
            ->limit(10)
            ->get();

        $activeAlerts = Alert::with('pond')
            ->whereIn('pond_id', $pondIds)
            ->where('status', 'active')
            ->latest('detected_at')
            ->limit(10)
            ->get();

        return view('dashboard', [
            'ponds' => $ponds,
            'pondCount' => $ponds->count(),
            'deviceCount' => $deviceCount,
            'readingCount' => $readingCount,
            'activeAlertCount' => $activeAlertCount,
            'latestReadings' => $latestReadings,
            'activeAlerts' => $activeAlerts,
        ]);
    }
}
