<?php

declare(strict_types=1);

namespace Tests\Browser\OperatorJourney\Scenarios;

use Tests\Browser\OperatorJourney\Support\OperatorScenario;

final class ScenarioCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function journeyDefinitions(): array
    {
        return [
            [
                'id' => OperatorScenario::ADMIN_LOGIN_ASSIGN->value,
                'name' => 'Administrator provisioning',
                'entry_route' => '/login-user',
                'steps' => [
                    '/login-user',
                    '/company',
                    '/division',
                    '/site',
                    '/user',
                ],
            ],
            [
                'id' => OperatorScenario::OPS_OFFLINE_RECOVERY->value,
                'name' => 'Operations recovery workflow',
                'entry_route' => '/dashboard',
                'steps' => [
                    '/dashboard',
                    '/gateway',
                    '/gateway/get_update_csv',
                    '/gateway/force_lp',
                ],
            ],
            [
                'id' => OperatorScenario::MAINT_CONFIGURATION->value,
                'name' => 'Maintenance configuration workflow',
                'entry_route' => '/site',
                'steps' => [
                    '/site',
                    '/gateway',
                    '/meter',
                    '/configuration-file',
                ],
            ],
            [
                'id' => OperatorScenario::ANALYST_REPORT_EXPORT->value,
                'name' => 'Analyst reporting workflow',
                'entry_route' => '/dashboard',
                'steps' => [
                    '/raw',
                    '/site-report',
                    '/sap',
                    '/demand',
                    '/raw_excel',
                ],
            ],
        ];
    }
}
