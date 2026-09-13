<?php

namespace App\Http\Controllers;

use App\Models\Pond;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PondThresholdController extends Controller
{
    public function store(Request $request, Pond $pond): RedirectResponse
    {
        abort_unless(
            $pond->fish_farm_id === $request->user()->fish_farm_id,
            404,
        );

        $temperatureMaxRules = ['nullable', 'numeric'];
        $phMaxRules = ['nullable', 'numeric', 'between:0,14'];
        $waterLevelMaxRules = ['nullable', 'numeric'];

        if ($request->filled('temperature_min')) {
            $temperatureMaxRules[] = 'gte:temperature_min';
        }

        if ($request->filled('ph_min')) {
            $phMaxRules[] = 'gte:ph_min';
        }

        if ($request->filled('water_level_min')) {
            $waterLevelMaxRules[] = 'gte:water_level_min';
        }

        $validated = $request->validate([
            'temperature_min' => ['nullable', 'numeric'],
            'temperature_max' => $temperatureMaxRules,
            'ph_min' => ['nullable', 'numeric', 'between:0,14'],
            'ph_max' => $phMaxRules,
            'turbidity_max' => ['nullable', 'numeric', 'min:0'],
            'water_level_min' => ['nullable', 'numeric', 'min:0'],
            'water_level_max' => $waterLevelMaxRules,
        ]);

        $pond->threshold()->updateOrCreate([], $validated);

        return redirect("/ponds/{$pond->id}");
    }
}
