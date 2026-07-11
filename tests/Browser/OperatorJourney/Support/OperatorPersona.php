<?php

declare(strict_types=1);

namespace Tests\Browser\OperatorJourney\Support;

enum OperatorPersona: string
{
    case ADMINISTRATOR = 'administrator';
    case OPERATIONS_ENGINEER = 'operations_engineer';
    case MAINTENANCE_TECHNICIAN = 'maintenance_technician';
    case ANALYST = 'analyst';

    public function description(): string
    {
        return match ($this) {
            self::ADMINISTRATOR => 'System admin with full workflow permissions.',
            self::OPERATIONS_ENGINEER => 'Operations engineer focused on maintenance and recovery actions.',
            self::MAINTENANCE_TECHNICIAN => 'Maintenance technician validating configuration and meter actions.',
            self::ANALYST => 'Analyst working report and dashboard review workflows.',
        };
    }

    public function credentials(): array
    {
        return match ($this) {
            self::ADMINISTRATOR => [
                'seed_email' => 'admin@demo.local',
                'seed_password' => '123456',
                'goal' => 'run setup, approvals, and user scoping',
            ],
            self::OPERATIONS_ENGINEER => [
                'seed_email' => 'ops-eng.demo@camr.local',
                'seed_password' => 'Demo@1234',
                'goal' => 'monitor and resolve operational status transitions',
            ],
            self::MAINTENANCE_TECHNICIAN => [
                'seed_email' => 'maintenance_demo@camr.local',
                'seed_password' => 'Demo@1234',
                'goal' => 'perform maintenance-oriented site and gateway tasks',
            ],
            self::ANALYST => [
                'seed_email' => 'analyst.demo@camr.local',
                'seed_password' => 'Demo@1234',
                'goal' => 'consume report and export workflows',
            ],
        };
    }
}
