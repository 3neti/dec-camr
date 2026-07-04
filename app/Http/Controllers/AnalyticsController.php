<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class AnalyticsController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Analytics', [
            'title' => 'Analytics Workbench',
            'subtitle' => 'Investigate historical consumption, demand, comparison, and load-profile evidence without replacing Reports.',
            'status' => [
                'label' => 'Shell Ready',
                'description' => 'The workbench route is mounted. Data wiring remains intentionally deferred to the next analytics work item.',
            ],
            'workbenchSections' => [
                [
                    'id' => 'consumption',
                    'title' => 'Consumption',
                    'description' => 'Use ConsumptionSeriesPoint data to understand usage patterns over time.',
                    'contract' => 'ConsumptionSeriesPoint',
                ],
                [
                    'id' => 'demand',
                    'title' => 'Demand',
                    'description' => 'Use DemandSeriesPoint data to identify peak demand windows and drivers.',
                    'contract' => 'DemandSeriesPoint',
                ],
                [
                    'id' => 'building-comparison',
                    'title' => 'Building Comparison',
                    'description' => 'Use BuildingConsumptionSummary data to compare buildings for a selected period.',
                    'contract' => 'BuildingConsumptionSummary',
                ],
                [
                    'id' => 'load-profile',
                    'title' => 'Load Profile',
                    'description' => 'Combine consumption and demand series for a focused investigation.',
                    'contract' => 'ConsumptionSeriesPoint + DemandSeriesPoint',
                ],
            ],
            'readinessChecklist' => [
                'Run php artisan camr:scenario analytics-demo for deterministic showcase data.',
                'Select scope and time range after AN-017 wires contract data.',
                'Keep Reports as the formal workbook/export workflow.',
            ],
        ]);
    }
}
