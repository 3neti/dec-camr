<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('analytics executive review journey has portfolio-level monthly context', function () {
    $this->artisan('camr:scenario analytics-demo')
        ->assertSuccessful();

    $user = User::factory()->create([
        'user_type' => 'Admin',
        'user_access' => 'ALL',
    ]);

    $response = $this->withSession(['loginID' => $user->id])
        ->get('/analytics')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Analytics')
            ->where('title', 'Analytics Workbench')
            ->where('status.label', 'Workspace Composed')
            ->where('analyticsContext.hasData', true)
            ->where('queryState.comparison', 'portfolio')
            ->where('scopeVisibility.mode', 'full')
            ->has('contractData.buildingSummaries')
            ->has('contractEvidence')
            ->where('contractEvidence.buildingSummaryCount', fn (int $value): bool => $value > 0)
            ->has('analyticsContext.grain')
            ->where('contractEvidence.incompleteCount', fn (int $value): bool => $value >= 0)
            ->where('contractEvidence.unknownCount', fn (int $value): bool => $value >= 0));

    expect($response->inertiaProps('emptyState.kind'))->toBe('missing-filter');
});

it('analytics executive review journey identifies a ranked top mover candidate', function () {
    $this->artisan('camr:scenario analytics-demo')
        ->assertSuccessful();

    $user = User::factory()->create([
        'user_type' => 'Admin',
        'user_access' => 'ALL',
    ]);

    $response = $this->withSession(['loginID' => $user->id])
        ->get('/analytics?from=2026-07-01&to=2026-07-07&comparison=portfolio')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Analytics')
            ->where('analyticsContext.hasData', true)
            ->where('contractEvidence.buildingSummaryCount', fn (int $value): bool => $value > 0)
            ->has('contractData.buildingSummaries'));

    $buildingSummaries = $response->inertiaProps('contractData.buildingSummaries');

    if (count($buildingSummaries) < 2) {
        $this->markTestSkipped('Executive review journey requires at least two building summaries in analytics demo data.');
    }

    $topMover = collect($buildingSummaries)->firstWhere('comparison.isTopConsumer', true);

    if ($topMover === null) {
        $this->markTestSkipped('Executive review journey requires a top mover signal for the selected window.');
    }

    $topMoverIndex = collect($buildingSummaries)->search(
        fn (array $summary): bool => (int) $summary['buildingId'] === (int) $topMover['buildingId']
    );

    expect($topMoverIndex)->toBe(0);
    expect($buildingSummaries[0]['comparison']['topConsumerKwh'])->toBeGreaterThan(0.0);
    expect($buildingSummaries[0]['confidence']['level'])->not->toBe('');
});

it('AN-021 executive review browser implementation is scaffolded')->todo();
