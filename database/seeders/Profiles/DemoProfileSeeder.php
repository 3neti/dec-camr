<?php

declare(strict_types=1);

namespace Database\Seeders\Profiles;

use App\Models\Company;
use App\Models\Division;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Collection;

final class DemoProfileSeeder extends AbstractProfileSeeder
{
    public function seed(User $admin, Division $division, Company $company): void
    {
        $configurationFiles = $this->ensureConfigurationFiles($admin, [
            'zmd402_serial_2400.cfg' => 'zmd402_serial_2400',
            'zmd402_serial.cfg' => 'zmd402_serial',
            's4e.cfg' => 'S4E',
        ]);

        $companies = [
            $company,
            $this->ensureCompany($admin, 'Northline Energy Group', 'NEG01'),
            $this->ensureCompany($admin, 'Southline Power Services', 'SPS01'),
        ];

        $divisions = [
            ['company' => $companies[0], 'code' => 'D-OPS', 'name' => 'Ops Division'],
            ['company' => $companies[0], 'code' => 'D-FI', 'name' => 'Field Division'],
            ['company' => $companies[1], 'code' => 'D-MT', 'name' => 'Maintenance Division'],
            ['company' => $companies[2], 'code' => 'D-AN', 'name' => 'Analytics Division'],
        ];

        $divisionModels = [];

        foreach ($divisions as $item) {
            $divisionModels[] = [
                'company' => $item['company'],
                'model' => $this->ensureDivision($admin, $item['company'], $item['code'], $item['name']),
            ];
        }

        $meters = new Collection;
        $siteMap = [];
        $meterIndex = 1;
        $gatewayIndex = 1;

        foreach ($divisionModels as $divisionIndex => $divisionBundle) {
            $division = $divisionBundle['model'];
            $divisionCompany = $divisionBundle['company'];

            $sitesByDivision = 3;
            for ($siteOrdinal = 1; $siteOrdinal <= $sitesByDivision; $siteOrdinal++) {
                $siteCode = sprintf('SITE-D%02d-%02d', $divisionIndex + 1, $siteOrdinal);
                $site = $this->ensureSite(
                    $admin,
                    $divisionCompany,
                    $division,
                    $siteCode,
                    sprintf('Demo Site %02d-%02d', $divisionIndex + 1, $siteOrdinal),
                    null,
                );

                $building = $this->ensureBuilding(
                    $admin,
                    $site,
                    sprintf('BLD-%s-%02d', $division->division_code, $siteOrdinal),
                    sprintf('Building %02d for %s', $siteOrdinal, $siteCode),
                    22,
                );

                $site->update(['building_idx' => $building->building_id]);

                $locations = [
                    $this->ensureLocation(
                        $admin,
                        $site,
                        sprintf('L%s-1', $siteCode),
                        'Main Distribution Floor',
                        0,
                    ),
                    $this->ensureLocation(
                        $admin,
                        $site,
                        sprintf('L%s-2', $siteCode),
                        'Auxiliary Floor',
                        0,
                    ),
                ];

                $siteOffset = 3;
                for ($g = 1; $g <= 2; $g++) {
                    $gatewayOffset = match ((($divisionIndex + $siteOrdinal + $g) % 3)) {
                        0 => 8,
                        1 => 40,
                        default => 85,
                    };

                    $gateway = $this->ensureGateway(
                        $admin,
                        $site,
                        $locations[$g - 1],
                        sprintf('DM-GW-%03d', $gatewayIndex++),
                        sprintf('aa:bb:cc:dd:%02x:%02x', $gatewayOffset, $g),
                        sprintf('10.20.%d.%d', $siteOrdinal, $g),
                        $gatewayOffset,
                        (($gatewayIndex + $divisionIndex) % 4 === 0) ? 1 : 0,
                        0,
                        (($gatewayOffset < 20) ? 1 : 0),
                        (($gatewayOffset > 80) ? 1 : 0),
                        (float) $gatewayOffset > 80.0 ? '2.12' : '2.10',
                    );

                    $location = $locations[$siteOffset % 2];

                    $meterTarget = 2 + (($gatewayIndex + $siteOrdinal) % 2);
                    for ($m = 1; $m <= $meterTarget; $m++) {
                        $meters->push(
                            $this->ensureMeter(
                                $admin,
                                $site,
                                $gateway,
                                $location,
                                $building,
                                (int) $configurationFiles['zmd402_serial.cfg'],
                                sprintf('MDTR-%03d', $meterIndex),
                                $siteCode,
                                sprintf('Demo Customer %02d', $meterIndex),
                                (($m % 2) === 0) ? 'Billing Meter' : 'Client Meter',
                                (($meterIndex % 2) === 0) ? 'YES' : 'NO',
                                'ACTIVE',
                            )
                        );

                        $meterIndex++;
                    }

                    $siteOffset++;
                }

                $siteMap[] = $site->site_id;
            }
        }

        $adminUser = $this->ensureUser(
            $admin,
            'ops_admin_demo',
            'admin.demo@camr.local',
            'Operations Admin',
            'Platform Administrator',
            'Admin',
            'ALL',
        );

        $siteReadUser = $this->ensureUser(
            $admin,
            'ops_eng_demo',
            'ops-eng.demo@camr.local',
            'Operations Engineer',
            'Operations Engineer',
            'User',
            'Selected',
        );

        $maintUser = $this->ensureUser(
            $admin,
            'maintenance_demo',
            'maint.demo@camr.local',
            'Maintenance Technician',
            'Maintenance',
            'User',
            'Selected',
        );

        $analystUser = $this->ensureUser(
            $admin,
            'analyst_demo',
            'analyst.demo@camr.local',
            'Data Analyst',
            'Reporting Analyst',
            'User',
            'Selected',
        );

        $allSites = Site::query()->whereIn('site_id', $siteMap)->orderBy('site_id')->get();

        $allSites->each(function (Site $site, int $index) use ($admin, $adminUser, $siteReadUser, $maintUser, $analystUser): void {
            $this->ensureUserSiteAccess($adminUser, (int) $site->site_id, $admin);

            if ($index < 6) {
                $this->ensureUserSiteAccess($siteReadUser, (int) $site->site_id, $admin);
            }

            if ($index >= 4 && $index < 10) {
                $this->ensureUserSiteAccess($maintUser, (int) $site->site_id, $admin);
            }

            if (($index % 2) === 0) {
                $this->ensureUserSiteAccess($analystUser, (int) $site->site_id, $admin);
            }
        });

        $this->seedTelemetryHistory($meters, 24, 12);
    }

    private function ensureCompany(User $admin, string $name, string $code): Company
    {
        return Company::query()->updateOrCreate(
            ['company_name' => $name],
            [
                'company_code' => $code,
                'created_by_user_idx' => $admin->id,
                'modified_by_user_idx' => $admin->id,
            ]
        );
    }
}
