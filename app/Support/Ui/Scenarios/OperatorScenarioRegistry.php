<?php

declare(strict_types=1);

namespace App\Support\Ui\Scenarios;

final class OperatorScenarioRegistry
{
    /**
     * @return array<string, OperatorScenarioDefinition>
     */
    public function all(): array
    {
        return $this->definitions();
    }

    public function find(string $scenarioKey): ?OperatorScenarioDefinition
    {
        $normalized = strtolower(trim($scenarioKey));

        return $this->definitions()[$normalized] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function describe(string $scenarioKey): array
    {
        $definition = $this->find($scenarioKey);

        if ($definition === null) {
            return [];
        }

        return [
            'key' => $definition->key,
            'title' => $definition->title,
            'persona' => $definition->persona,
            'seed_profile' => $definition->seedProfile,
            'simulator_scenario' => $definition->simulatorScenario,
            'simulator_duration' => $definition->simulatorDuration,
            'simulator_speed' => $definition->simulatorSpeed,
            'deterministic_anchor' => $definition->deterministicAnchor,
            'starting_state' => $definition->startingState,
            'expected_outcome' => $definition->expectedOutcome,
        ];
    }

    /**
     * @return array<string, OperatorScenarioDefinition>
     */
    private function definitions(): array
    {
        return [
            'admin-provisioning' => new OperatorScenarioDefinition(
                key: 'admin-provisioning',
                title: 'Administrator provisioning and site-user workflow',
                persona: 'Administrator',
                seedProfile: 'demo',
                simulatorScenario: 'normal',
                simulatorDuration: '10m',
                simulatorSpeed: 'real',
                deterministicAnchor: null,
                startingState: 'Core admin and maintenance entities exist; scoped users are not yet active.',
                expectedOutcome: 'Admin can provision core entities and assign scoped users for maintenance visibility checks.',
                suggestedTestFilter: '--filter=admin-login-assign',
                demoNotes: 'Use for baseline admin onboarding and provisioning smoke.',
            ),
            'operations-gateway-recovery' => new OperatorScenarioDefinition(
                key: 'operations-gateway-recovery',
                title: 'Operations engineer offline recovery and lifecycle validation',
                persona: 'Operations Engineer',
                seedProfile: 'demo',
                simulatorScenario: 'offline-recovery',
                simulatorDuration: '30m',
                simulatorSpeed: 'real',
                deterministicAnchor: null,
                startingState: 'Mixed gateway status set contains stale and recovering entities.',
                expectedOutcome: 'Operations sees stale/offline transitions and confirms recovery path.',
                suggestedTestFilter: '--filter=ops-offline-recovery',
                demoNotes: 'Use for operations-focused UI dry-runs and stability checks.',
            ),
            'maintenance-meter-update' => new OperatorScenarioDefinition(
                key: 'maintenance-meter-update',
                title: 'Maintenance workflow across site, gateway, and meter updates',
                persona: 'Maintenance Technician',
                seedProfile: 'demo',
                simulatorScenario: 'normal',
                simulatorDuration: '20m',
                simulatorSpeed: 'fast',
                deterministicAnchor: null,
                startingState: 'Maintenance entities are present with periodic update flags.',
                expectedOutcome: 'Maintenance can move through maintenance edit paths and update marker lifecycle.',
                suggestedTestFilter: '--filter=maintenance-configuration',
                demoNotes: 'Pairs well with mutation-heavy maintenance journey runs.',
            ),
            'analyst-report-export' => new OperatorScenarioDefinition(
                key: 'analyst-report-export',
                title: 'Analyst report and workbook export smoke',
                persona: 'Analyst',
                seedProfile: 'demo',
                simulatorScenario: 'report-window',
                simulatorDuration: '65m',
                simulatorSpeed: 'fast',
                deterministicAnchor: null,
                startingState: 'Report-ready telemetry window starts at small cadence.',
                expectedOutcome: 'Analyst can run representative reports and verify export command targets.',
                suggestedTestFilter: '--filter=analyst-report-export',
                demoNotes: 'Use for report UX rehearsals before dashboard/report slices.',
            ),
            'fresh-install-smoke' => new OperatorScenarioDefinition(
                key: 'fresh-install-smoke',
                title: 'Fresh-install smoke and baseline visibility',
                persona: 'Administrator',
                seedProfile: 'minimal',
                simulatorScenario: 'normal',
                simulatorDuration: '5m',
                simulatorSpeed: 'real',
                deterministicAnchor: '2026-07-01 08:00:00',
                startingState: 'Minimal dataset loaded for fast startup checks.',
                expectedOutcome: 'Fresh install can render a complete minimal operator path with deterministic telemetry.',
                suggestedTestFilter: '--filter=fresh-install-smoke',
                demoNotes: 'Use after database reset for quick verification sessions.',
            ),
            'heavy-data-readiness' => new OperatorScenarioDefinition(
                key: 'heavy-data-readiness',
                title: 'Heavy dataset stress-readiness and pagination behavior',
                persona: 'Operations Engineer',
                seedProfile: 'heavy',
                simulatorScenario: 'report-window',
                simulatorDuration: '1h',
                simulatorSpeed: 'fast',
                deterministicAnchor: 'now',
                startingState: 'Large cardinality dataset with wide meter coverage.',
                expectedOutcome: 'High-volume pages remain responsive and report windows are populated.',
                suggestedTestFilter: '--filter=heavy-data-readiness',
                demoNotes: 'Use before performance-focused UI slicing.',
            ),
            'analytics-demo' => new OperatorScenarioDefinition(
                key: 'analytics-demo',
                title: 'Analytics showcase data readiness',
                persona: 'Energy Manager',
                seedProfile: 'demo',
                simulatorScenario: 'analytics-demo',
                simulatorDuration: '24h',
                simulatorSpeed: 'fast',
                deterministicAnchor: '2026-07-01 08:00:00',
                startingState: 'Demo maintenance entities exist with selected meters prepared for analytics showcase telemetry.',
                expectedOutcome: 'Analytics contracts expose normal consumption, abnormal high consumption, demand peaks, incomplete windows, unknown windows, and ranked building comparisons.',
                suggestedTestFilter: '--filter=analytics-demo-data-readiness',
                demoNotes: 'Use before mounting Analytics UI so future charts have visible analytical patterns instead of flat rows.',
            ),
            'live-scada-demo' => new OperatorScenarioDefinition(
                key: 'live-scada-demo',
                title: 'Live SCADA-style telemetry replay',
                persona: 'Operations Engineer',
                seedProfile: 'demo',
                simulatorScenario: 'file-replay',
                simulatorDuration: '25m',
                simulatorSpeed: 'fast',
                deterministicAnchor: '2026-07-01 08:00:00',
                startingState: 'Demo maintenance entities exist and the replay file contains gateway-shaped telemetry rows.',
                expectedOutcome: 'Dashboard and Analytics show live-ingest-derived telemetry, recovery, demand, and consumption signals.',
                suggestedTestFilter: '--filter=live-scada-demo',
                demoNotes: 'Use when reviewing CAMR as a live operational console backed by replayed gateway posts.',
                telemetryReplayFile: database_path('fixtures/telemetry/scada-demo-readings.csv'),
            ),
        ];
    }
}
