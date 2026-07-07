<?php

declare(strict_types=1);

namespace App\Support\Ui\Scenarios;

use App\Actions\Ui\SimulateTelemetryAction;
use App\Actions\Rtu\ReplayTelemetryFileAction;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;

final class OperatorScenarioRunner
{
    public function __construct(
        private readonly OperatorScenarioRegistry $registry,
        private readonly SimulateTelemetryAction $simulator,
        private readonly ?ReplayTelemetryFileAction $replayTelemetryFile = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function run(
        string $scenarioKey,
        bool $dryRun = false,
        bool $runSeed = true,
        bool $runSimulation = true,
        ?string $anchor = null,
        bool $allowProduction = false,
    ): array {
        $definition = $this->registry->find($scenarioKey);

        if ($definition === null) {
            throw new InvalidArgumentException(sprintf('Unknown scenario: %s', $scenarioKey));
        }

        if (
            app()->environment('production')
            && ! $allowProduction
            && ! $dryRun
            && ($runSeed || $runSimulation)
        ) {
            throw new InvalidArgumentException('camr:scenario is disabled in production. Use --allow-production if intentional.');
        }

        $effectiveAnchor = $anchor === null || trim($anchor) === '' ? $definition->deterministicAnchor : trim($anchor);

        if (! $runSeed) {
            $seedSummary = [
                'performed' => false,
                'status' => 'skipped',
                'profile' => $definition->seedProfile,
            ];
        } else {
            if ($dryRun) {
                $seedSummary = [
                    'performed' => false,
                    'status' => 'dry-run',
                    'profile' => $definition->seedProfile,
                    'message' => 'Seed profile skipped for dry-run.',
                ];
            } else {
                Artisan::call('camr:seed-profile', [
                    '--profile' => $definition->seedProfile,
                ]);
                $seedSummary = [
                    'performed' => true,
                    'status' => 'completed',
                    'profile' => $definition->seedProfile,
                    'output' => trim((string) Artisan::output()),
                ];
            }
        }

        if (! $runSimulation) {
            $simulateSummary = [
                'performed' => false,
                'status' => 'skipped',
                'scenario' => $definition->simulatorScenario,
            ];
        } elseif ($definition->telemetryReplayFile !== null) {
            $replayTelemetryFile = $this->replayTelemetryFile ?? app(ReplayTelemetryFileAction::class);
            $simulateSummary = [
                'performed' => true,
                'status' => $dryRun ? 'dry-run' : 'completed',
                'scenario' => $definition->simulatorScenario,
                'source' => 'file-replay',
                'file' => $definition->telemetryReplayFile,
            ];

            $simulateSummary['summary'] = $replayTelemetryFile->replay(
                file: $definition->telemetryReplayFile,
                dryRun: $dryRun,
                speed: $definition->simulatorSpeed,
                anchor: $effectiveAnchor,
            );
        } else {
            $simulateSummary = [
                'performed' => true,
                'status' => $dryRun ? 'dry-run' : 'completed',
                'scenario' => $definition->simulatorScenario,
            ];

            $simulateSummary['summary'] = $this->simulator->simulate(
                durationInput: $definition->simulatorDuration,
                speed: $definition->simulatorSpeed,
                scenario: $definition->simulatorScenario,
                profile: $definition->seedProfile,
                dryRun: $dryRun,
                deterministic: true,
                anchor: $effectiveAnchor,
            );
        }

        return [
            'scenario' => $definition->toArray(),
            'run' => [
                'dry_run' => $dryRun,
                'seed' => $seedSummary,
                'simulate' => $simulateSummary,
            ],
            'metadata' => [
                'starting_state' => $definition->startingState,
                'expected_outcome' => $definition->expectedOutcome,
                'suggested_test_filter' => $definition->suggestedTestFilter,
            ],
        ];
    }
}
