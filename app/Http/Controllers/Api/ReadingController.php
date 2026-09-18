<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\ReadingIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingController extends Controller
{
    public function store(
        Request $request,
        ReadingIngestionService $readingIngestionService,
    ): JsonResponse
    {
        $validated = $request->validate([
            'device_uid' => ['required', 'string', 'exists:devices,device_uid'],
            'temperature' => ['required', 'numeric', 'between:0,60'],
            'ph' => ['required', 'numeric', 'between:0,14'],
            'turbidity' => ['required', 'numeric', 'min:0'],
            'water_level' => ['required', 'numeric', 'min:0'],
        ]);

        $device = Device::where('device_uid', $validated['device_uid'])->firstOrFail();

        $result = $readingIngestionService->ingest($device, $validated);

        return response()->json([
            'message' => 'Lectura registrada correctamente',
            'data' => $result->reading,
        ], 201);
    }
}
