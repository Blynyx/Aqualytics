<?php

namespace App\Services;

use App\Models\Device;
use App\Models\PondThreshold;
use Random\Randomizer;

class TelemetrySampleGenerator
{
    public const MODE_NORMAL = 'normal';

    public const MODE_ANOMALY = 'anomaly';

    public const MODE_MIXED = 'mixed';

    /**
     * Safe simulation ranges used only when the pond has no configured threshold.
     *
     * @var array<string, array{min: float, max: float}>
     */
    public const FALLBACK_RANGES = [
        'temperature' => ['min' => 22.0, 'max' => 28.0],
        'ph' => ['min' => 6.5, 'max' => 8.0],
        'turbidity' => ['min' => 10.0, 'max' => 40.0],
        'water_level' => ['min' => 60.0, 'max' => 90.0],
    ];

    /**
     * Physical limits aligned with POST /api/readings (plus a practical cap
     * where the API only enforces a minimum).
     *
     * @var array<string, array{min: float, max: float}>
     */
    public const PHYSICAL_BOUNDS = [
        'temperature' => ['min' => 0.0, 'max' => 60.0],
        'ph' => ['min' => 0.0, 'max' => 14.0],
        'turbidity' => ['min' => 0.0, 'max' => 500.0],
        'water_level' => ['min' => 0.0, 'max' => 200.0],
    ];

    /**
     * @var array<string, float>
     */
    private const STEPS = [
        'temperature' => 0.15,
        'ph' => 0.04,
        'turbidity' => 1.2,
        'water_level' => 0.6,
    ];

    /**
     * Moderate overshoot used for anomalies.
     *
     * @var array<string, array{min: float, max: float}>
     */
    private const ANOMALY_DELTAS = [
        'temperature' => ['min' => 0.6, 'max' => 2.4],
        'ph' => ['min' => 0.2, 'max' => 0.8],
        'turbidity' => ['min' => 5.0, 'max' => 18.0],
        'water_level' => ['min' => 3.0, 'max' => 10.0],
    ];

    /**
     * @var list<string>
     */
    private const PARAMETERS = ['temperature', 'ph', 'turbidity', 'water_level'];

    /**
     * Last healthy walk values kept in memory for this command run.
     *
     * @var array<string, float>
     */
    private array $current = [];

    public function __construct(
        private Device $device,
        private string $mode = self::MODE_NORMAL,
        private int $anomalyRate = 10,
        private ?Randomizer $randomizer = null,
    ) {
        $this->randomizer ??= new Randomizer;
        $this->current = $this->initialValues();
    }

    /**
     * @return array{temperature: float, ph: float, turbidity: float, water_level: float}
     */
    public function next(): array
    {
        $values = [];

        foreach (self::PARAMETERS as $parameter) {
            $this->current[$parameter] = $this->walk($parameter, $this->current[$parameter]);
            $values[$parameter] = $this->current[$parameter];
        }

        if ($this->shouldEmitAnomaly()) {
            $values = $this->applyAnomaly($values);
        }

        return $this->roundValues($values);
    }

    private function shouldEmitAnomaly(): bool
    {
        return match ($this->mode) {
            self::MODE_ANOMALY => true,
            self::MODE_MIXED => $this->anomalyRate > 0 && $this->randomizer->getInt(1, 100) <= $this->anomalyRate,
            default => false,
        };
    }

    private function walk(string $parameter, float $current): float
    {
        $range = $this->rangeFor($parameter);
        $step = self::STEPS[$parameter];
        $next = $current + $this->randomFloat(-$step, $step);

        return $this->clamp($next, $range['min'], $range['max']);
    }

    /**
     * @param  array<string, float>  $values
     * @return array<string, float>
     */
    private function applyAnomaly(array $values): array
    {
        $threshold = $this->threshold();
        $candidates = [];

        foreach (self::PARAMETERS as $parameter) {
            [$minColumn, $maxColumn] = match ($parameter) {
                'temperature' => ['temperature_min', 'temperature_max'],
                'ph' => ['ph_min', 'ph_max'],
                'turbidity' => [null, 'turbidity_max'],
                'water_level' => ['water_level_min', 'water_level_max'],
            };

            $hasMin = $minColumn !== null && $threshold?->{$minColumn} !== null;
            $hasMax = $maxColumn !== null && $threshold?->{$maxColumn} !== null;

            if ($threshold !== null && ! $hasMin && ! $hasMax) {
                continue;
            }

            $range = $this->rangeFor($parameter);
            $bounds = self::PHYSICAL_BOUNDS[$parameter];
            $delta = self::ANOMALY_DELTAS[$parameter];

            if (($threshold === null || $hasMax) && $range['max'] + $delta['min'] <= $bounds['max']) {
                $candidates[] = [$parameter, 'high'];
            }

            if (($threshold === null || $hasMin) && $range['min'] - $delta['min'] >= $bounds['min']) {
                $candidates[] = [$parameter, 'low'];
            }
        }

        if ($candidates === []) {
            return $values;
        }

        [$parameter, $direction] = $candidates[$this->randomizer->getInt(0, count($candidates) - 1)];
        $range = $this->rangeFor($parameter);
        $bounds = self::PHYSICAL_BOUNDS[$parameter];
        $delta = self::ANOMALY_DELTAS[$parameter];
        $overshoot = $this->randomFloat($delta['min'], $delta['max']);

        $anomalous = $direction === 'high'
            ? $range['max'] + $overshoot
            : $range['min'] - $overshoot;

        $values[$parameter] = $this->clamp($anomalous, $bounds['min'], $bounds['max']);

        return $values;
    }

    /**
     * @return array<string, float>
     */
    private function initialValues(): array
    {
        $values = [];

        foreach (self::PARAMETERS as $parameter) {
            $range = $this->rangeFor($parameter);
            $values[$parameter] = ($range['min'] + $range['max']) / 2;
        }

        return $values;
    }

    /**
     * @return array{min: float, max: float}
     */
    private function rangeFor(string $parameter): array
    {
        $fallback = self::FALLBACK_RANGES[$parameter];
        $threshold = $this->threshold();

        [$minColumn, $maxColumn] = match ($parameter) {
            'temperature' => ['temperature_min', 'temperature_max'],
            'ph' => ['ph_min', 'ph_max'],
            'turbidity' => [null, 'turbidity_max'],
            'water_level' => ['water_level_min', 'water_level_max'],
        };

        $min = $minColumn !== null && $threshold?->{$minColumn} !== null
            ? (float) $threshold->{$minColumn}
            : $fallback['min'];
        $max = $maxColumn !== null && $threshold?->{$maxColumn} !== null
            ? (float) $threshold->{$maxColumn}
            : $fallback['max'];

        if ($min > $max) {
            return $fallback;
        }

        return ['min' => $min, 'max' => $max];
    }

    private function threshold(): ?PondThreshold
    {
        return $this->device->pond?->threshold;
    }

    /**
     * @param  array<string, float>  $values
     * @return array{temperature: float, ph: float, turbidity: float, water_level: float}
     */
    private function roundValues(array $values): array
    {
        return [
            'temperature' => round($values['temperature'], 2),
            'ph' => round($values['ph'], 2),
            'turbidity' => round($values['turbidity'], 2),
            'water_level' => round($values['water_level'], 2),
        ];
    }

    private function randomFloat(float $min, float $max): float
    {
        if ($min === $max) {
            return $min;
        }

        return $min + ($this->randomizer->getInt(0, 1_000_000) / 1_000_000) * ($max - $min);
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
