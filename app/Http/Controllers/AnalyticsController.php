<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Analytics\BuildAnalyticsWorkbenchDataAction;
use Inertia\Inertia;
use Inertia\Response;

final class AnalyticsController extends Controller
{
    public function __construct(
        private readonly BuildAnalyticsWorkbenchDataAction $buildAnalyticsWorkbenchData,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('Analytics', [
            'title' => 'Analytics Workbench',
            'subtitle' => 'Investigate historical consumption, demand, comparison, and load-profile evidence without replacing Reports.',
            'status' => [
                'label' => 'Workspace Composed',
                'description' => 'The workbench now composes real analytics contract data into the first visible historical analysis workspace.',
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
                'Review contract evidence before visual workspace composition.',
                'Keep Reports as the formal workbook/export workflow.',
            ],
            'exportPanel' => [
                'title' => 'Evidence Export Panel',
                'description' => 'Use Analytics to frame the evidence package, then open Reports for formal workbook exports.',
                'actions' => [
                    [
                        'id' => 'consumption-report',
                        'label' => 'Open Consumption Report',
                        'description' => 'Use the formal report workflow when this evidence needs workbook output.',
                        'reportFamily' => 'consumption',
                    ],
                    [
                        'id' => 'demand-report',
                        'label' => 'Open Demand Report',
                        'description' => 'Validate peak demand evidence against the approved Demand report workflow.',
                        'reportFamily' => 'demand',
                    ],
                    [
                        'id' => 'raw-report',
                        'label' => 'Open Raw Report',
                        'description' => 'Inspect source telemetry rows behind the analytical evidence.',
                        'reportFamily' => 'raw',
                    ],
                    [
                        'id' => 'site-report',
                        'label' => 'Open Site Report',
                        'description' => 'Review building and site-level report output without changing Analytics semantics.',
                        'reportFamily' => 'site',
                    ],
                ],
                'preservationNote' => 'Analytics explains evidence. Reports remain the approved workflow for formal XLSX and workbook exports.',
            ],
            ...$this->buildAnalyticsWorkbenchData->execute(),
        ]);
    }
}
