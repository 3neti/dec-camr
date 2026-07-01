<?php

declare(strict_types=1);

namespace Database\Seeders\Profiles;

use App\Models\Company;
use App\Models\Division;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Collection;

final class MinimalProfileSeeder extends AbstractProfileSeeder
{
    public function seed(User $admin, Division $division, Company $company): void
    {
        $configurationFiles = $this->ensureConfigurationFiles($admin, [
            'zmd402_serial_2400.cfg' => 'zmd402_serial_2400',
            'zmd402_serial.cfg' => 'zmd402_serial',
            's4e.cfg' => 'S4E',
        ]);

        $operationsDivision = $this->ensureDivision($admin, $company, 'D-OPS', 'Operations Division');
        $fieldDivision = $this->ensureDivision($admin, $company, 'D-FIELD', 'Field Services');

        $sites = collect([
            [
                'site_code' => 'SITE-MIN-01',
                'site_name' => 'Main Operations',
                'division' => $operationsDivision,
                'building_code' => 'BLD-MIN-01',
                'building_desc' => 'Tower Building 01',
                'locations' => ['OPS-PL1' => 'Plant Floor L1', 'OPS-PL2' => 'Plant Floor L2'],
                'gateways' => [
                    ['sn' => '2019MN0001', 'mac' => 'aa:bb:cc:dd:01:01', 'ip' => '10.10.10.11', 'offset' => 4, 'flags' => [1, 1, 0, 0]],
                    ['sn' => '2019MN0002', 'mac' => 'aa:bb:cc:dd:01:02', 'ip' => '10.10.10.12', 'offset' => 55, 'flags' => [0, 1, 0, 0]],
                ],
            ],
            [
                'site_code' => 'SITE-MIN-02',
                'site_name' => 'North Distribution',
                'division' => $fieldDivision,
                'building_code' => 'BLD-MIN-02',
                'building_desc' => 'Distribution Point 02',
                'locations' => ['N-PL1' => 'Feeder Room', 'N-PL2' => 'Control Rack'],
                'gateways' => [
                    ['sn' => '2019MN0003', 'mac' => 'aa:bb:cc:dd:01:03', 'ip' => '10.10.10.13', 'offset' => 20, 'flags' => [0, 0, 0, 0]],
                ],
            ],
            [
                'site_code' => 'SITE-MIN-03',
                'site_name' => 'East Campus',
                'division' => $operationsDivision,
                'building_code' => 'BLD-MIN-03',
                'building_desc' => 'Utility Campus',
                'locations' => ['E-PL1' => 'Transformer Hall', 'E-PL2' => 'Back Office'],
                'gateways' => [
                    ['sn' => '2019MN0004', 'mac' => 'aa:bb:cc:dd:01:04', 'ip' => '10.10.10.14', 'offset' => 140, 'flags' => [0, 0, 1, 0]],
                    ['sn' => '2019MN0005', 'mac' => 'aa:bb:cc:dd:01:05', 'ip' => '10.10.10.15', 'offset' => 300, 'flags' => [0, 0, 1, 1]],
                ],
            ],
        ]);

        $meters = new Collection;

        $meterSeq = 1;
        foreach ($sites as $siteDefinition) {
            $building = $this->ensureBuilding(
                $admin,
                $site = $this->ensureSite(
                    $admin,
                    $company,
                    $siteDefinition['division'],
                    $siteDefinition['site_code'],
                    $siteDefinition['site_name'],
                    null,
                ),
                $siteDefinition['building_code'],
                $siteDefinition['building_desc'],
                20,
            );

            $site->update(['building_idx' => $building->building_id]);

            $locations = [];
            foreach ($siteDefinition['locations'] as $locationCode => $locationName) {
                $locations[] = $this->ensureLocation(
                    $admin,
                    $site,
                    $locationCode,
                    $locationName,
                    0,
                );
            }

            foreach ($siteDefinition['gateways'] as $gatewayIndex => $gatewayDefinition) {
                $location = $locations[$gatewayIndex % count($locations)];

                $gateway = $this->ensureGateway(
                    $admin,
                    $site,
                    $location,
                    (string) $gatewayDefinition['sn'],
                    (string) $gatewayDefinition['mac'],
                    (string) $gatewayDefinition['ip'],
                    (int) $gatewayDefinition['offset'],
                    (int) $gatewayDefinition['flags'][0],
                    (int) $gatewayDefinition['flags'][1],
                    (int) $gatewayDefinition['flags'][2],
                    (int) $gatewayDefinition['flags'][3],
                    ((int) $gatewayIndex % 2 === 0) ? '2.10' : '2.09',
                );

                $metersPerGateway = match ((int) $gatewayIndex) {
                    0 => 3,
                    1 => 2,
                    default => 2,
                };

                for ($offset = 1; $offset <= $metersPerGateway; $offset++) {
                    $meter = $this->ensureMeter(
                        $admin,
                        $site,
                        $gateway,
                        $location,
                        $building,
                        (int) $configurationFiles['s4e.cfg'],
                        sprintf('MTR-%03d', $meterSeq++),
                        $siteDefinition['site_code'],
                        sprintf('Customer %02d', $meterSeq),
                        'Client Meter',
                        'NO',
                    );

                    $meters->push($meter);
                }
            }
        }

        $adminProfile = $this->ensureUser(
            $admin,
            'admin_phase0',
            'admin@demo.local',
            'Platform Admin',
            'Administrator',
            'Admin',
            'ALL',
        );

        $operationsUser = $this->ensureUser(
            $admin,
            'ops_minimal',
            'ops@demo.local',
            'Operations Technician',
            'Operations Technician',
            'User',
            'Selected',
        );

        $analystUser = $this->ensureUser(
            $admin,
            'analyst_minimal',
            'analyst@demo.local',
            'Data Analyst',
            'Reporting Analyst',
            'User',
            'Selected',
        );

        $sites->each(function (array $siteDefinition) use ($adminProfile, $operationsUser, $analystUser): void {
            $site = Site::query()->where('site_code', $siteDefinition['site_code'])->first();
            if ($site === null) {
                return;
            }

            $this->ensureUserSiteAccess($adminProfile, (int) $site->site_id, $adminProfile);
            $this->ensureUserSiteAccess($operationsUser, (int) $site->site_id, $adminProfile);

            if ((string) $siteDefinition['site_code'] !== 'SITE-MIN-02') {
                $this->ensureUserSiteAccess($analystUser, (int) $site->site_id, $adminProfile);
            }
        });

        $this->seedTelemetryHistory($meters, 18, 10);
    }
}
