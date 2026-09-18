<?php

namespace App\Services;

use App\Models\FishFarm;
use App\Models\Pond;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ReadingHistoryService
{
    public const DEFAULT_RANGE = '24h';

    /**
     * Hard cap for the first version: send at most the newest 500 points
     * in the requested window, ordered ASC. No aggregation yet.
     */
    public const MAX_POINTS = 500;

    /**
     * @var array<string, array{label: string, required_days: int}>
     */
    public const RANGES = [
        '24h' => ['label' => 'Últimas 24 h', 'required_days' => 1],
        '7d' => ['label' => '7 días', 'required_days' => 7],
        '30d' => ['label' => '30 días', 'required_days' => 30],
        '90d' => ['label' => '90 días', 'required_days' => 90],
    ];

    public function historyDaysFor(FishFarm $fishFarm): int
    {
        return (int) ($fishFarm->subscription?->plan?->history_days ?? 0);
    }

    /**
     * @return list<string>
     */
    public function allowedRanges(FishFarm $fishFarm): array
    {
        $historyDays = $this->historyDaysFor($fishFarm);

        return array_values(array_filter(
            array_keys(self::RANGES),
            fn (string $range): bool => $historyDays >= self::RANGES[$range]['required_days'],
        ));
    }

    public function resolveRange(?string $range, FishFarm $fishFarm): string
    {
        $range = $range ?: self::DEFAULT_RANGE;

        if (! array_key_exists($range, self::RANGES)) {
            throw ValidationException::withMessages([
                'range' => 'El rango solicitado no es válido.',
            ]);
        }

        if ($this->historyDaysFor($fishFarm) < self::RANGES[$range]['required_days']) {
            throw ValidationException::withMessages([
                'range' => 'Tu plan no incluye este periodo de historial.',
            ]);
        }

        return $range;
    }

    public function since(string $range, ?CarbonInterface $now = null): Carbon
    {
        $now = Carbon::parse($now ?? now());

        return $range === '24h'
            ? $now->copy()->subHours(24)
            : $now->copy()->subDays((int) rtrim($range, 'd'));
    }

    /**
     * @return Collection<int, array{recorded_at: string, temperature: float, ph: float, turbidity: float, water_level: float}>
     */
    public function readingsFor(Pond $pond, string $range): Collection
    {
        $readings = $pond->readings()
            ->where('recorded_at', '>=', $this->since($range))
            ->orderByDesc('recorded_at')
            ->limit(self::MAX_POINTS)
            ->get()
            ->reverse()
            ->values();

        return $readings->map(fn ($reading): array => [
            'recorded_at' => Carbon::parse($reading->recorded_at)->toIso8601String(),
            'temperature' => (float) $reading->temperature,
            'ph' => (float) $reading->ph,
            'turbidity' => (float) $reading->turbidity,
            'water_level' => (float) $reading->water_level,
        ]);
    }
}
