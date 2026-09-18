<?php

namespace App\Http\Controllers;

use App\Models\Pond;
use App\Services\ReadingHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PondReadingHistoryController extends Controller
{
    public function __invoke(
        Request $request,
        Pond $pond,
        ReadingHistoryService $history,
    ): JsonResponse {
        abort_unless(
            $pond->fish_farm_id === $request->user()->fish_farm_id,
            404,
        );

        $range = $history->resolveRange(
            $request->query('range'),
            $request->user()->fishFarm,
        );

        return response()->json([
            'pond' => [
                'id' => $pond->id,
                'name' => $pond->name,
            ],
            'range' => $range,
            'readings' => $history->readingsFor($pond, $range),
        ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
