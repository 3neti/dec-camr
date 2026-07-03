<?php

test('analytics empty state component documents supported unavailable states', function () {
    $component = file_get_contents(resource_path('js/components/analytics/AnalyticsEmptyState.vue'));

    expect($component)->toContain("type EmptyStateKind = 'missing-filter' | 'no-data' | 'incomplete-data' | 'unsupported-grain'")
        ->and($component)->toContain('Choose an analytical scope')
        ->and($component)->toContain('No telemetry matches this selection')
        ->and($component)->toContain('Analytics evidence is incomplete')
        ->and($component)->toContain('This aggregation is not available yet')
        ->and($component)->toContain('Missing intervals:')
        ->and($component)->toContain('Grain:')
        ->and($component)->toContain('Source: {{ sourceLabel }}');
});

test('analytics empty state remains route neutral before analytics page mounting', function () {
    $component = file_get_contents(resource_path('js/components/analytics/AnalyticsEmptyState.vue'));

    expect($component)->not->toContain('@inertiajs/vue3')
        ->and($component)->not->toContain('<Link')
        ->and($component)->not->toContain('href=');
});
