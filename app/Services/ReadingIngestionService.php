<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Carbon;

class ReadingIngestionService
{
    public function __construct(
        private AlertEvaluationService $alertEvaluationService,
    ) {}

    /**
     * Persist a sensor payload, refresh device presence and evaluate alerts.
     *
     * @param  array{temperature: float|int|string, ph: float|int|string, turbidity: float|int|string, water_level: float|int|string}  $sensorValues
     */
    public function ingest(Device $device, array $sensorValues, ?Carbon $recordedAt = null): ReadingIngestionResult
    {
        $recordedAt ??= now();

        $reading = $device->readings()->create([
            'pond_id' => $device->pond_id,
            'temperature' => $sensorValues['temperature'],
            'ph' => $sensorValues['ph'],
            'turbidity' => $sensorValues['turbidity'],
            'water_level' => $sensorValues['water_level'],
            'recorded_at' => $recordedAt,
        ]);

        $device->update([
            'last_seen_at' => $recordedAt,
        ]);

        $this->alertEvaluationService->evaluate($reading->load('pond.threshold'));

        $reading->load('alerts');

        return new ReadingIngestionResult($reading, $reading->alerts);
    }
}
