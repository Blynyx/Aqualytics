<?php

namespace App\Services;

use App\Models\Reading;
use Illuminate\Support\Collection;

class ReadingIngestionResult
{
    public function __construct(
        public readonly Reading $reading,
        public readonly Collection $alerts,
    ) {}

    public function hasAnomaly(): bool
    {
        return $this->alerts->isNotEmpty();
    }
}
