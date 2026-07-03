<?php

use App\Support\Analytics\DataTrustIndicator;
use App\Support\Analytics\TelemetryPointConfidence;

test('data trust indicator represents calculated analytics evidence without warning', function () {
    $indicator = DataTrustIndicator::make(
        level: TelemetryPointConfidence::Calculated,
        reason: 'Building total calculated from complete consumption series points.',
        source: [
            'contract' => 'BuildingConsumptionSummary',
            'sourceContract' => 'ConsumptionSeriesPoint',
        ],
        sourceLineage: [
            'contract' => 'BuildingConsumptionSummary',
            'includedMeterCount' => 2,
        ],
    )->toArray();

    expect($indicator['level'])->toBe('Calculated')
        ->and($indicator['reason'])->toBe('Building total calculated from complete consumption series points.')
        ->and($indicator['missingIntervalCount'])->toBe(0)
        ->and($indicator['source']['contract'])->toBe('BuildingConsumptionSummary')
        ->and($indicator['source']['sourceContract'])->toBe('ConsumptionSeriesPoint')
        ->and($indicator['warning'])->toBeNull()
        ->and($indicator['sourceLineage']['includedMeterCount'])->toBe(2);
});

test('data trust indicator creates user-facing warning for incomplete evidence', function () {
    $indicator = DataTrustIndicator::make(
        level: 'Incomplete',
        reason: 'Consumption window is missing a required boundary reading.',
        missingIntervalCount: 2,
        source: [
            'contract' => 'ConsumptionSeriesPoint',
            'sourceContract' => 'TelemetryPoint',
        ],
    )->toArray();

    expect($indicator['level'])->toBe('Incomplete')
        ->and($indicator['missingIntervalCount'])->toBe(2)
        ->and($indicator['warning'])->toBe('Analysis is incomplete because 2 expected interval(s) are missing.');
});

test('data trust indicator preserves unknown and estimated uncertainty warnings', function (string $level, string $expectedWarning) {
    $indicator = DataTrustIndicator::make(
        level: $level,
        reason: 'Analytics evidence requires review.',
    )->toArray();

    expect($indicator['level'])->toBe($level)
        ->and($indicator['warning'])->toBe($expectedWarning);
})->with([
    'unknown evidence' => ['Unknown', 'Analysis includes values that cannot be confidently interpreted without review.'],
    'estimated evidence' => ['Estimated', 'Analysis includes estimated values.'],
]);

test('data trust indicator normalizes existing analytics confidence arrays', function () {
    $indicator = DataTrustIndicator::fromAnalyticsEvidence(
        confidence: [
            'level' => 'Incomplete',
            'reason' => 'Building summary includes incomplete consumption windows.',
        ],
        missingData: [
            'missingIntervalCount' => 3,
            'incompleteSeriesPointCount' => 2,
        ],
        sourceLineage: [
            'contract' => 'BuildingConsumptionSummary',
            'sourceContract' => 'ConsumptionSeriesPoint',
            'includedMeterCount' => 4,
        ],
    )->toArray();

    expect($indicator['level'])->toBe('Incomplete')
        ->and($indicator['reason'])->toBe('Building summary includes incomplete consumption windows.')
        ->and($indicator['missingIntervalCount'])->toBe(3)
        ->and($indicator['source']['contract'])->toBe('BuildingConsumptionSummary')
        ->and($indicator['source']['sourceContract'])->toBe('ConsumptionSeriesPoint')
        ->and($indicator['sourceLineage']['includedMeterCount'])->toBe(4)
        ->and($indicator['warning'])->toBe('Analysis is incomplete because 3 expected interval(s) are missing.');
});

test('data trust indicator rejects unsupported trust levels', function () {
    DataTrustIndicator::make(
        level: 'Confident',
        reason: 'Unsupported trust level.',
    );
})->throws(InvalidArgumentException::class, 'Unsupported data trust level [Confident].');
