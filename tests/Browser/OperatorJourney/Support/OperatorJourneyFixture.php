<?php

declare(strict_types=1);

namespace Tests\Browser\OperatorJourney\Support;

final class OperatorJourneyFixture
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function profileSeeds(): array
    {
        return [
            'minimal' => [
                'command' => 'php artisan camr:seed-profile --profile=minimal',
                'notes' => 'Fast local dataset with core entity graph.',
            ],
            'demo' => [
                'command' => 'php artisan camr:seed-profile --profile=demo',
                'notes' => 'Operationally rich demonstration dataset.',
            ],
            'heavy' => [
                'command' => 'php artisan camr:seed-profile --profile=heavy',
                'notes' => 'Large dataset for list/report stress and scaling.',
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function simulatorScenarios(): array
    {
        return [
            'normal' => [
                'command' => 'php artisan camr:simulate --scenario=normal --duration=10m --speed=real',
                'notes' => 'Baseline telemetry wave with periodic pending-update flags.',
            ],
            'offline-recovery' => [
                'command' => 'php artisan camr:simulate --scenario=offline-recovery --duration=10m --speed=real',
                'notes' => 'Gateway staleness and staged return to fresh updates.',
            ],
            'report-window' => [
                'command' => 'php artisan camr:simulate --scenario=report-window --duration=20m --speed=fast',
                'notes' => 'Window-oriented dataset growth for report verification.',
            ],
        ];
    }
}
