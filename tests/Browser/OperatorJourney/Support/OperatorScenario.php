<?php

declare(strict_types=1);

namespace Tests\Browser\OperatorJourney\Support;

enum OperatorScenario: string
{
    case ADMIN_LOGIN_ASSIGN = 'admin_login_assign';
    case OPS_OFFLINE_RECOVERY = 'ops_offline_recovery';
    case MAINT_CONFIGURATION = 'maintenance_configuration';
    case ANALYST_REPORT_EXPORT = 'analyst_report_export';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN_LOGIN_ASSIGN => 'Administrator: Login → Company/Division/Site/User',
            self::OPS_OFFLINE_RECOVERY => 'Operations Engineer: Dashboard → Offline Gateway → Force LP',
            self::MAINT_CONFIGURATION => 'Maintenance: Site → Gateway → Meter → Configuration',
            self::ANALYST_REPORT_EXPORT => 'Analyst: Reports → Download → Workbook Validation',
        };
    }

    public function objective(): string
    {
        return match ($this) {
            self::ADMIN_LOGIN_ASSIGN => 'Validate high-privilege provisioning workflow with scoped impacts.',
            self::OPS_OFFLINE_RECOVERY => 'Validate operational observation and recovery-oriented actions.',
            self::MAINT_CONFIGURATION => 'Validate maintenance route access and edit sequencing.',
            self::ANALYST_REPORT_EXPORT => 'Validate report filter, output, and export handoff.',
        };
    }
}
