<?php

namespace App\Services;

use App\Models\Reading;

class AlertEvaluationService
{
    public function evaluate(Reading $reading): void
    {
        $threshold = $reading->pond->threshold;

        if ($threshold === null) {
            return;
        }

        if ($threshold->temperature_min !== null
            && $reading->temperature < $threshold->temperature_min) {
            $this->createAlert(
                $reading,
                'temperature',
                $reading->temperature,
                $threshold->temperature_min,
                null,
                'Temperatura por debajo del rango configurado',
            );
        }

        if ($threshold->temperature_max !== null
            && $reading->temperature > $threshold->temperature_max) {
            $this->createAlert(
                $reading,
                'temperature',
                $reading->temperature,
                null,
                $threshold->temperature_max,
                'Temperatura por encima del rango configurado',
            );
        }

        if ($threshold->ph_min !== null && $reading->ph < $threshold->ph_min) {
            $this->createAlert(
                $reading,
                'ph',
                $reading->ph,
                $threshold->ph_min,
                null,
                'pH por debajo del rango configurado',
            );
        }

        if ($threshold->ph_max !== null && $reading->ph > $threshold->ph_max) {
            $this->createAlert(
                $reading,
                'ph',
                $reading->ph,
                null,
                $threshold->ph_max,
                'pH por encima del rango configurado',
            );
        }

        if ($threshold->turbidity_max !== null
            && $reading->turbidity > $threshold->turbidity_max) {
            $this->createAlert(
                $reading,
                'turbidity',
                $reading->turbidity,
                null,
                $threshold->turbidity_max,
                'Turbidez por encima del máximo configurado',
            );
        }

        if ($threshold->water_level_min !== null
            && $reading->water_level < $threshold->water_level_min) {
            $this->createAlert(
                $reading,
                'water_level',
                $reading->water_level,
                $threshold->water_level_min,
                null,
                'Nivel de agua por debajo del rango configurado',
            );
        }

        if ($threshold->water_level_max !== null
            && $reading->water_level > $threshold->water_level_max) {
            $this->createAlert(
                $reading,
                'water_level',
                $reading->water_level,
                null,
                $threshold->water_level_max,
                'Nivel de agua por encima del rango configurado',
            );
        }
    }

    private function createAlert(
        Reading $reading,
        string $parameter,
        mixed $value,
        mixed $minThreshold,
        mixed $maxThreshold,
        string $message,
    ): void {
        $reading->alerts()->create([
            'pond_id' => $reading->pond_id,
            'device_id' => $reading->device_id,
            'parameter' => $parameter,
            'value' => $value,
            'min_threshold' => $minThreshold,
            'max_threshold' => $maxThreshold,
            'severity' => 'warning',
            'status' => 'active',
            'message' => $message,
            'detected_at' => now(),
        ]);
    }
}
