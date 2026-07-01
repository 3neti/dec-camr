<?php

declare(strict_types=1);

namespace App\Support\Ui\Scenarios;

final class OperatorScenarioDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $persona,
        public readonly string $seedProfile,
        public readonly string $simulatorScenario,
        public readonly string $simulatorDuration,
        public readonly string $simulatorSpeed,
        public readonly ?string $deterministicAnchor,
        public readonly string $startingState,
        public readonly string $expectedOutcome,
        public readonly ?string $suggestedTestFilter,
        public readonly ?string $demoNotes,
    ) {}

    /**
     * @return array<string, string|bool|null>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'persona' => $this->persona,
            'seed_profile' => $this->seedProfile,
            'simulator_scenario' => $this->simulatorScenario,
            'simulator_duration' => $this->simulatorDuration,
            'simulator_speed' => $this->simulatorSpeed,
            'deterministic_anchor' => $this->deterministicAnchor,
            'starting_state' => $this->startingState,
            'expected_outcome' => $this->expectedOutcome,
            'suggested_test_filter' => $this->suggestedTestFilter,
            'demo_notes' => $this->demoNotes,
        ];
    }
}
