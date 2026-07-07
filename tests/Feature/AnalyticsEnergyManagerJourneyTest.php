<?php

use App\Actions\Analytics\BuildConsumptionSeriesAction;
use App\Actions\Analytics\BuildDemandSeriesAction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

test('analytics energy manager journey reaches evidence-ready workspace from analytics-demo', function () {
    $this->artisan('camr:scenario analytics-demo')
        ->assertSuccessful();

    $user = User::factory()->create([
        'user_type' => 'Admin',
        'user_access' => 'ALL',
    ]);

    $this->withSession(['loginID' => $user->id])
        ->get('/analytics')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Analytics')
            ->where('title', 'Analytics Workbench')
            ->where('status.label', 'Workspace Composed')
            ->where('analyticsContext.hasData', true)
            ->where('contractEvidence.consumptionPointCount', fn (int $value): bool => $value > 0)
            ->where('contractEvidence.demandPointCount', fn (int $value): bool => $value > 0)
            ->where('contractEvidence.buildingSummaryCount', fn (int $value): bool => $value > 0)
            ->where('queryState.comparison', 'portfolio')
            ->where('emptyState.kind', 'missing-filter')
            ->has('contractData.consumptionPoints')
            ->has('contractData.demandPoints')
            ->has('contractData.buildingSummaries')
            ->where('contextControls.buildingCode', fn (string $buildingCode): bool => $buildingCode !== '')
            ->where('contextControls.meterIdentifier', fn (string $meterIdentifier): bool => $meterIdentifier !== ''));
});

test('analytics energy manager journey can pivot to selected-building investigation mode', function () {
    $user = User::factory()->create([
        'user_type' => 'Admin',
        'user_access' => 'ALL',
    ]);

    $this->artisan('camr:scenario analytics-demo')
        ->assertSuccessful();

    $defaultResponse = $this->withSession(['loginID' => $user->id])
        ->get('/analytics')
        ->assertOk();

    $buildingOptions = collect($defaultResponse->inertiaProps('contextControls.buildingOptions'));
    $targetBuilding = $buildingOptions->first(fn (array $option): bool => $option['value'] !== $defaultResponse->inertiaProps('contextControls.buildingCode'));

    if ($targetBuilding === null) {
        $this->markTestSkipped('Energy manager journey requires at least two building options in demo data.');
    }

    $selectedDate = (string) $defaultResponse->inertiaProps('timeRangeControls.from');
    $selectedResponse = $this->withSession(['loginID' => $user->id])
        ->get('/analytics?building='.$targetBuilding['value'].'&from='.$selectedDate.'&to='.$selectedDate.'&comparison=selected')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('queryState.building', $targetBuilding['value'])
            ->where('queryState.comparison', 'selected')
            ->where('analyticsContext.buildingCode', $targetBuilding['value'])
            ->where('contractEvidence.buildingSummaryCount', fn (int $value): bool => $value >= 1)
            ->where('status.label', 'Workspace Composed')
            ->has('contractData.buildingSummaries'));

    $meterIdentifier = (string) $selectedResponse->inertiaProps('contextControls.meterIdentifier');
    $from = CarbonImmutable::parse((string) $selectedResponse->inertiaProps('timeRangeControls.from'))->startOfDay();
    $to = CarbonImmutable::parse((string) $selectedResponse->inertiaProps('timeRangeControls.to'))->endOfDay();

    $expectedConsumptionCount = count(
        app(BuildConsumptionSeriesAction::class)->execute(
            meterIdentifier: $meterIdentifier,
            buildingCode: (string) $selectedResponse->inertiaProps('analyticsContext.buildingCode'),
            from: $from,
            to: $to,
            grain: (string) $selectedResponse->inertiaProps('analyticsContext.grain'),
        )
    );

    $expectedDemandCount = count(
        app(BuildDemandSeriesAction::class)->execute(
            meterIdentifier: $meterIdentifier,
            buildingCode: (string) $selectedResponse->inertiaProps('analyticsContext.buildingCode'),
            from: $from,
            to: $to,
            grain: (string) $selectedResponse->inertiaProps('analyticsContext.grain'),
        )
    );

    expect($selectedResponse->inertiaProps('contractData.consumptionPoints'))->toHaveCount($expectedConsumptionCount);
    expect($selectedResponse->inertiaProps('contractData.demandPoints'))->toHaveCount($expectedDemandCount);
});

it('AN-020 energy manager journey browser implementation is scaffolded')->todo();
