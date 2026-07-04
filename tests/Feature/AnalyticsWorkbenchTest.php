<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('analytics workbench requires legacy login session', function () {
    $this->get('/analytics')
        ->assertRedirect('/')
        ->assertSessionHas('fail', 'You Have to Login First');
});

test('analytics workbench shell renders through inertia when loginID exists', function () {
    $user = User::factory()->create();

    $this->withSession(['loginID' => $user->id])
        ->get('/analytics')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Analytics')
            ->where('title', 'Analytics Workbench')
            ->where('status.label', 'Shell Ready')
            ->where('workbenchSections.0.id', 'consumption')
            ->where('workbenchSections.1.id', 'demand')
            ->where('workbenchSections.2.id', 'building-comparison')
            ->where('workbenchSections.3.id', 'load-profile')
            ->where('readinessChecklist.0', 'Run php artisan camr:scenario analytics-demo for deterministic showcase data.'));
});

test('analytics appears in operator shell navigation without replacing reports', function () {
    $navigation = file_get_contents(resource_path('js/config/operatorShellNavigation.ts'));

    expect($navigation)->toContain("title: 'Analytics'")
        ->and($navigation)->toContain("href: '/analytics'")
        ->and($navigation)->toContain("title: 'SAP Report'")
        ->and($navigation)->toContain("href: '/sap_report'");
});
