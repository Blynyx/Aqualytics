<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pond_id' => [
                'required',
                'integer',
                Rule::exists('ponds', 'id')
                    ->where('user_id', $request->user()->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'device_uid' => [
                'required',
                'string',
                'max:255',
                Rule::unique('devices', 'device_uid'),
            ],
        ]);

        $pond = $request->user()->ponds()->findOrFail($validated['pond_id']);

        $pond->devices()->create([
            'name' => $validated['name'],
            'device_uid' => $validated['device_uid'],
            'status' => 'active',
        ]);

        return redirect('/ponds');
    }
}
