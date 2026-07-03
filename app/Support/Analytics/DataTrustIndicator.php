<?php

declare(strict_types=1);

namespace App\Support\Analytics;

use InvalidArgumentException;

final readonly class DataTrustIndicator
{
    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $sourceLineage
     */
    public function __construct(
        public TelemetryPointConfidence $level,
        public string $reason,
        public int $missingIntervalCount,
        public array $source,
        public ?string $warning,
        public array $sourceLineage,
    ) {}

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $sourceLineage
     */
    public static function make(
        TelemetryPointConfidence|string $level,
        string $reason,
        int $missingIntervalCount = 0,
        array $source = [],
        ?string $warning = null,
        array $sourceLineage = [],
    ): self {
        $level = is_string($level) ? self::levelFromString($level) : $level;

        return new self(
            level: $level,
            reason: $reason,
            missingIntervalCount: max(0, $missingIntervalCount),
            source: $source,
            warning: $warning ?? self::defaultWarning($level, $missingIntervalCount),
            sourceLineage: $sourceLineage,
        );
    }

    /**
     * @param  array<string, mixed>  $confidence
     * @param  array<string, mixed>  $missingData
     * @param  array<string, mixed>  $sourceLineage
     */
    public static function fromAnalyticsEvidence(
        array $confidence,
        array $missingData,
        array $sourceLineage,
        ?string $sourceContract = null,
    ): self {
        $level = isset($confidence['level']) ? (string) $confidence['level'] : TelemetryPointConfidence::Unknown->value;
        $reason = isset($confidence['reason']) ? (string) $confidence['reason'] : 'Analytics confidence reason was not supplied.';
        $missingIntervalCount = isset($missingData['missingIntervalCount']) ? (int) $missingData['missingIntervalCount'] : 0;
        $contract = $sourceContract ?? (isset($sourceLineage['contract']) ? (string) $sourceLineage['contract'] : 'unknown');

        return self::make(
            level: $level,
            reason: $reason,
            missingIntervalCount: $missingIntervalCount,
            source: [
                'contract' => $contract,
                'sourceContract' => $sourceLineage['sourceContract'] ?? null,
            ],
            sourceLineage: $sourceLineage,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'level' => $this->level->value,
            'reason' => $this->reason,
            'missingIntervalCount' => $this->missingIntervalCount,
            'source' => $this->source,
            'warning' => $this->warning,
            'sourceLineage' => $this->sourceLineage,
        ];
    }

    private static function levelFromString(string $level): TelemetryPointConfidence
    {
        return TelemetryPointConfidence::tryFrom($level)
            ?? throw new InvalidArgumentException("Unsupported data trust level [{$level}].");
    }

    private static function defaultWarning(TelemetryPointConfidence $level, int $missingIntervalCount): ?string
    {
        if ($level === TelemetryPointConfidence::Incomplete) {
            return $missingIntervalCount > 0
                ? "Analysis is incomplete because {$missingIntervalCount} expected interval(s) are missing."
                : 'Analysis is incomplete because required evidence is missing.';
        }

        if ($level === TelemetryPointConfidence::Unknown) {
            return 'Analysis includes values that cannot be confidently interpreted without review.';
        }

        if ($level === TelemetryPointConfidence::Estimated) {
            return 'Analysis includes estimated values.';
        }

        return null;
    }
}
