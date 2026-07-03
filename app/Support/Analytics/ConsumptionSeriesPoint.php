<?php

declare(strict_types=1);

namespace App\Support\Analytics;

use Carbon\CarbonImmutable;

final readonly class ConsumptionSeriesPoint
{
    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>|null  $startReading
     * @param  array<string, mixed>|null  $endReading
     * @param  array<string, mixed>  $missingData
     * @param  array<string, mixed>  $confidence
     * @param  array<string, mixed>  $sourceLineage
     */
    public function __construct(
        public CarbonImmutable $periodStart,
        public CarbonImmutable $periodEnd,
        public string $grain,
        public array $context,
        public ?float $kwhTotal,
        public ?array $startReading,
        public ?array $endReading,
        public float $multiplier,
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
            'kwhTotal' => $this->kwhTotal,
            'startReading' => $this->startReading,
            'endReading' => $this->endReading,
            'multiplier' => $this->multiplier,
            'missingData' => $this->missingData,
            'confidence' => $this->confidence,
            'sourceLineage' => $this->sourceLineage,
        ];
    }
}
