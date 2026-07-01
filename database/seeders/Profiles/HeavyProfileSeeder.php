<?php

declare(strict_types=1);

namespace Database\Seeders\Profiles;

use App\Models\Company;
use App\Models\Division;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Collection;

final class HeavyProfileSeeder extends AbstractProfileSeeder
{
    public function seed(User $admin, Division $division, Company $company): void
    {
        $configurationFiles = $this->ensureConfigurationFiles($admin, [
            'zmd402_serial_2400.cfg' => 'zmd402_serial_2400',
            'zmd402_serial.cfg' => 'zmd402_serial',
            's4e.cfg' => 'S4E',
        ]);

        $companies = [$company];
        for ($companyOffset = 1; $companyOffset <= 2; $companyOffset++) {
            $companies[] = $this->ensureCompany($admin, sprintf('HeavyGrid Operations %02d', $companyOffset), sprintf('HGO%02d', $companyOffset));
        }

        $divisionBundles = [];
        $divisionCounter = 1;

        foreach ($companies as $companyModel) {
            for ($i = 0; $i < 3; $i++) {
                $divisionBundles[] = [
                    'company' => $companyModel,
                    'model' => $this->ensureDivision(
                        $admin,
                        $companyModel,
                        sprintf('H-DV-%02d', $divisionCounter),
                        sprintf('Heavy Division %02d', $divisionCounter),
                    ),
                ];
                $divisionCounter++;
            }
        }

        $meters = new Collection;
        $siteIds = [];

        $meterCounter = 1;
        $gatewayCounter = 1;

        foreach ($divisionBundles as $divisionIndex => $divisionBundle) {
            $division = $divisionBundle['model'];
            $divisionCompany = $divisionBundle['company'];

            for ($siteIdx = 1; $siteIdx <= 4; $siteIdx++) {
                $siteCode = sprintf('SITE-H%02d-%02d', $divisionIndex + 1, $siteIdx);
                $site = $this->ensureSite(
                    $admin,
                    $divisionCompany,
                    $division,
                    $siteCode,
                    sprintf('Heavy Site %02d-%02d', $divisionIndex + 1, $siteIdx),
                    null,
                );

                $building = $this->ensureBuilding(
                    $admin,
                    $site,
                    sprintf('BLD-H-%02d-%02d', $divisionIndex + 1, $siteIdx),
                    sprintf('Heavy Building %02d-%02d', $divisionIndex + 1, $siteIdx),
                    25,
                );

                $site->update(['building_idx' => $building->building_id]);
                $siteIds[] = $site->site_id;

                $locations = [];
                $locations[] = $this->ensureLocation(
                    $admin,
                    $site,
                    sprintf('HL-%02d-A', $siteIdx),
                    'High Volume Location A',
                );
                $locations[] = $this->ensureLocation(
                    $admin,
                    $site,
                    sprintf('HL-%02d-B', $siteIdx),
                    'High Volume Location B',
                );

                for ($gatewayIdx = 1; $gatewayIdx <= 2; $gatewayIdx++) {
                    $gatewayOffset = (($divisionIndex * 11) + ($siteIdx * 7) + $gatewayIdx) % 90;
                    $gateway = $this->ensureGateway(
                        $admin,
                        $site,
                        $locations[($gatewayOffset + $gatewayIdx) % 2],
                        sprintf('HV-GW-%03d', $gatewayCounter),
                        sprintf('aa:cc:dd:ee:%02x:%02x', $divisionIndex + 1, $gatewayIdx),
                        sprintf('10.30.%02d.%02d', $divisionIndex + 1, $gatewayIdx),
                        (int) $gatewayOffset,
                        (($gatewayOffset + $gatewayIdx) % 6 === 0) ? 1 : 0,
                        (($gatewayOffset + $gatewayIdx) % 5 === 0) ? 1 : 0,
                        0,
                        (($gatewayOffset + $gatewayIdx) % 4 === 0) ? 1 : 0,
                        (string) (number_format(2.05 + (($gatewayOffset % 7) * 0.01), 2, '.', '')),
                    );

                    $gatewayCounter++;

                    $metersForGateway = 2 + (($gatewayOffset + $gatewayIdx + $siteIdx) % 2);
                    for ($meterOffset = 0; $meterOffset < $metersForGateway; $meterOffset++) {
                        $meters->push(
                            $this->ensureMeter(
                                $admin,
                                $site,
                                $gateway,
                                $locations[($siteIdx + $gatewayIdx + $meterOffset) % 2],
                                $building,
                                (int) $configurationFiles['zmd402_serial_2400.cfg'],
                                sprintf('HVMTR-%03d', $meterCounter),
                                $siteCode,
                                sprintf('Heavy Customer %03d', $meterCounter),
                                'Client Meter',
                                (($meterOffset + $gatewayOffset) % 2 === 0) ? 'YES' : 'NO',
                            )
                        );

                        $meterCounter++;
                    }
                }
            }
        }

        $adminUser = $this->ensureUser(
            $admin,
            'ops_admin_heavy',
            'admin.heavy@camr.local',
            'Heavy Scenario Admin',
            'Operations Lead',
            'Admin',
            'ALL',
        );

        $opsUsers = [
            ['name' => 'ops_heavy_a', 'email' => 'ops.a.heavy@camr.local', 'real' => 'Ops Tech A', 'job' => 'Operations'],
            ['name' => 'ops_heavy_b', 'email' => 'ops.b.heavy@camr.local', 'real' => 'Ops Tech B', 'job' => 'Operations'],
            ['name' => 'maint_heavy', 'email' => 'maint.heavy@camr.local', 'real' => 'Maintenance Tech', 'job' => 'Maintenance'],
            ['name' => 'analyst_heavy', 'email' => 'analyst.heavy@camr.local', 'real' => 'Analyst', 'job' => 'Reporting'],
        ];

        $createdUsers = [];
        foreach ($opsUsers as $opsUser) {
            $createdUsers[] = $this->ensureUser(
                $admin,
                $opsUser['name'],
                $opsUser['email'],
                $opsUser['real'],
                $opsUser['job'],
                'User',
                'Selected',
            );
        }

        $allSites = Site::query()->whereIn('site_id', $siteIds)->orderBy('site_id')->get();
        $allSites->each(function (Site $site) use ($admin, $adminUser, $createdUsers): void {
            $this->ensureUserSiteAccess($adminUser, (int) $site->site_id, $admin);

            foreach ($createdUsers as $index => $createdUser) {
                if ((($site->site_id + $index) % 2) === 0) {
                    $this->ensureUserSiteAccess($createdUser, (int) $site->site_id, $admin);
                }
            }
        });

        $this->seedTelemetryHistory($meters, 12, 8);
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
