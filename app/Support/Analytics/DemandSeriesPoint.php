<?php

declare(strict_types=1);

namespace App\Support\Analytics;

use Carbon\CarbonImmutable;

final readonly class DemandSeriesPoint
{
    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>|null  $minReading
     * @param  array<string, mixed>|null  $maxReading
     * @param  array<string, mixed>  $peakMarker
     * @param  array<string, mixed>  $missingData
     * @param  array<string, mixed>  $confidence
     * @param  array<string, mixed>  $sourceLineage
     */
    public function __construct(
        public CarbonImmutable $periodStart,
        public CarbonImmutable $periodEnd,
        public string $grain,
        public array $context,
        public ?float $kwDemand,
        public ?array $minReading,
        public ?array $maxReading,
        public ?float $elapsedMinutes,
        public float $multiplier,
        public array $peakMarker,
        public array $missingData,
        public array $confidence,
        public array $sourceLineage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'periodStart' => $this->periodStart->toIso8601String(),
            'periodEnd' => $this->periodEnd->toIso8601String(),
            'grain' => $this->grain,
            'context' => $this->context,
            'kwDemand' => $this->kwDemand,
            'minReading' => $this->minReading,
            'maxReading' => $this->maxReading,
            'elapsedMinutes' => $this->elapsedMinutes,
            'multiplier' => $this->multiplier,
            'peakMarker' => $this->peakMarker,
            'missingData' => $this->missingData,
            'confidence' => $this->confidence,
            'sourceLineage' => $this->sourceLineage,
        ];
    }
}
