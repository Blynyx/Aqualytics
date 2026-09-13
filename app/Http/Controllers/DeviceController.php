<?php

namespace App\Http\Controllers;

use App\Models\Pond;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        $pond->devices()->create([
            'name' => $validated['name'],
            'device_uid' => $validated['device_uid'],
            'status' => 'active',
        ]);

        return redirect($nestedUnderPond ? "/ponds/{$pond->id}" : '/ponds');
    }
}
