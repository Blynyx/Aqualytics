<?php

namespace App\Http\Controllers;

use App\Models\Pond;
use App\Services\SubscriptionLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeviceController extends Controller
{
    public function store(Request $request, ?Pond $pond = null): RedirectResponse
    {
        $nestedUnderPond = $pond !== null;

        if ($pond !== null) {
            abort_unless(
                $pond->fish_farm_id === $request->user()->fish_farm_id,
                404,
            );

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'device_uid' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('devices', 'device_uid'),
                ],
            ]);
        } else {
            $validated = $request->validate([
                'pond_id' => [
                    'required',
                    'integer',
                    Rule::exists('ponds', 'id')
                        ->where('fish_farm_id', $request->user()->fish_farm_id),
                ],
                'name' => ['required', 'string', 'max:255'],
                'device_uid' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('devices', 'device_uid'),
                ],
            ]);

            $pond = $request->user()
                ->fishFarm
                ->ponds()
                ->findOrFail($validated['pond_id']);
        }

        $limits = app(SubscriptionLimitService::class);

        if (! $limits->canCreateDevice($request->user()->fishFarm)) {
            throw ValidationException::withMessages([
                'device_uid' => 'Has alcanzado el límite de dispositivos de tu plan.',
            ]);
        }

        $pond->devices()->create([
            'name' => $validated['name'],
            'device_uid' => $validated['device_uid'],
            'status' => 'active',
        ]);

        return redirect($nestedUnderPond ? "/ponds/{$pond->id}" : '/ponds');
    }
}
