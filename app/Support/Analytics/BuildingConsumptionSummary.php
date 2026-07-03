<?php

declare(strict_types=1);

namespace App\Support\Analytics;

final readonly class BuildingConsumptionSummary
{
    /**
     * @param  array<string, mixed>  $site
     * @param  array<string, mixed>  $missingData
     * @param  array<string, mixed>  $comparison
     * @param  array<string, mixed>  $confidence
     * @param  array<string, mixed>  $sourceLineage
     */
    public function __construct(
        public int $buildingId,
        public string $buildingCode,
        public string $buildingName,
        public array $site,
        public float $totalKwh,
        public int $meterCount,
        public int $seriesPointCount,
        public array $missingData,
        public array $comparison,
        public array $confidence,
        public array $sourceLineage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'buildingId' => $this->buildingId,
            'buildingCode' => $this->buildingCode,
            'buildingName' => $this->buildingName,
            'site' => $this->site,
            'totalKwh' => $this->totalKwh,
            'meterCount' => $this->meterCount,
            'seriesPointCount' => $this->seriesPointCount,
            'missingData' => $this->missingData,
            'comparison' => $this->comparison,
            'confidence' => $this->confidence,
            'sourceLineage' => $this->sourceLineage,
        ];
    }
}
