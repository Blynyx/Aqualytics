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
        $identity = $request->validate([
            'device_uid' => ['required', 'string', 'exists:devices,device_uid'],
        ]);

        $device = Device::where('device_uid', $identity['device_uid'])->firstOrFail();

        if (! $device->tokenMatches($request->header('X-Device-Token'))) {
            return response()->json([
                'message' => 'Dispositivo no autorizado.',
            ], 401);
        }

        $validated = $request->validate([
            'temperature' => ['required', 'numeric', 'between:0,60'],
            'ph' => ['required', 'numeric', 'between:0,14'],
            'turbidity' => ['required', 'numeric', 'min:0'],
            'water_level' => ['required', 'numeric', 'min:0'],
        ]);

        $result = $readingIngestionService->ingest($device, $validated);
        $reading = $result->reading->withoutRelations();

        return response()->json([
            'message' => 'Lectura registrada correctamente',
            'data' => $reading,
        ], 201);
    }
}
