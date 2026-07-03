<?php

declare(strict_types=1);

namespace App\Support\Analytics;

use Carbon\CarbonImmutable;

final readonly class TelemetryPoint
{
    /**
     * @param  array<string, mixed>  $measurements
     * @param  array<string, mixed>  $confidence
     * @param  array<string, mixed>  $sourceLineage
     */
    public function __construct(
        public int $id,
        public string $rawMeterIdentifier,
        public string $rawLocation,
        public CarbonImmutable $timestamp,
        public ?int $meterId,
        public ?string $meterName,
        public ?int $siteId,
        public ?string $siteCode,
        public ?int $buildingId,
        public ?string $buildingCode,
        public ?int $gatewayId,
        public ?string $gatewaySn,
        public ?string $gatewayMac,
        public array $measurements,
        public array $confidence,
        public array $sourceLineage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'rawMeterIdentifier' => $this->rawMeterIdentifier,
            'rawLocation' => $this->rawLocation,
            'timestamp' => $this->timestamp->toIso8601String(),
            'context' => [
                'meterId' => $this->meterId,
                'meterName' => $this->meterName,
                'siteId' => $this->siteId,
                'siteCode' => $this->siteCode,
                'buildingId' => $this->buildingId,
                'buildingCode' => $this->buildingCode,
                'gatewayId' => $this->gatewayId,
                'gatewaySn' => $this->gatewaySn,
                'gatewayMac' => $this->gatewayMac,
            ],
            'measurements' => $this->measurements,
            'confidence' => $this->confidence,
            'sourceLineage' => $this->sourceLineage,
        ];
    }
}
