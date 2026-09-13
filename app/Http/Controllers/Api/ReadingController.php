<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_uid' => ['required', 'string', 'exists:devices,device_uid'],
            'temperature' => ['required', 'numeric', 'between:0,60'],
            'ph' => ['required', 'numeric', 'between:0,14'],
            'turbidity' => ['required', 'numeric', 'min:0'],
            'water_level' => ['required', 'numeric', 'min:0'],
        ]);

        $device = Device::where('device_uid', $validated['device_uid'])->firstOrFail();
        $recordedAt = now();

        $reading = $device->readings()->create([
            'pond_id' => $device->pond_id,
            'temperature' => $validated['temperature'],
            'ph' => $validated['ph'],
            'turbidity' => $validated['turbidity'],
            'water_level' => $validated['water_level'],
            'recorded_at' => $recordedAt,
        ]);

        $device->update([
            'last_seen_at' => $recordedAt,
        ]);

        return response()->json([
            'message' => 'Lectura registrada correctamente',
            'data' => $reading,
        ], 201);
    }
}
