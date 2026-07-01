<?php

declare(strict_types=1);

namespace Database\Seeders\Profiles;

use App\Models\Building;
use App\Models\Company;
use App\Models\ConfigurationFile;
use App\Models\Division;
use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterData;
use App\Models\MeterLocation;
use App\Models\Site;
use App\Models\User;
use App\Models\UserSiteAccess;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

abstract class AbstractProfileSeeder
{
    public const DEFAULT_PASSWORD = 'Demo@1234';

    /**
     * @param  array<int, string>  $files
     * @return array<string, int>
     */
    protected function ensureConfigurationFiles(User $admin, array $files): array
    {
        $resolved = [];

        foreach ($files as $file => $model) {
            $config = ConfigurationFile::query()->updateOrCreate(
                ['config_file' => $file],
                [
                    'meter_model' => $model,
                    'created_by_user_idx' => $admin->id,
                    'modified_by_user_idx' => $admin->id,
                ]
            );

            $resolved[$file] = $config->config_id;
        }

        return $resolved;
    }

    protected function ensureDivision(User $admin, Company $company, string $divisionCode, string $divisionName): Division
    {
        return Division::query()->updateOrCreate(
            ['division_name' => $divisionName],
            [
                'division_code' => $divisionCode,
                'created_by_user_idx' => $admin->id,
                'modified_by_user_idx' => $admin->id,
                'company_idx' => $company->company_id,
            ]
        );
    }

    protected function ensureSite(
        User $admin,
        Company $company,
        Division $division,
        string $siteCode,
        string $buildingDescription,
        ?Building $building,
    ): Site {
        $site = Site::query()->updateOrCreate(
            ['site_code' => $siteCode],
            [
                'division_idx' => $division->division_id,
                'company_idx' => $company->company_id,
                'building_idx' => 0,
                'building_description' => $buildingDescription,
                'created_by_user_idx' => $admin->id,
                'modified_by_user_idx' => $admin->id,
            ]
        );

        if ($building !== null) {
            $site->update([
                'building_idx' => $building->building_id,
            ]);
        }

        return $site;
    }

    protected function ensureBuilding(
        User $admin,
        Site $site,
        string $buildingCode,
        string $buildingDescription,
        int $cutOff = 25,
    ): Building {
        return Building::query()->updateOrCreate(
            [
                'site_idx' => $site->site_id,
                'building_code' => $buildingCode,
            ],
            [
                'building_description' => $buildingDescription,
                'cut_off' => $cutOff,
                'created_by_user_idx' => $admin->id,
                'modified_by_user_idx' => $admin->id,
            ]
        );
    }

    protected function ensureLocation(
        User $admin,
        Site $site,
        string $locationCode,
        string $locationDescription,
        int $buildingId = 0,
    ): MeterLocation {
        return MeterLocation::query()->updateOrCreate(
            [
                'site_idx' => $site->site_id,
                'location_code' => $locationCode,
            ],
            [
                'building_id' => $buildingId,
                'location_description' => $locationDescription,
                'created_by_user_idx' => $admin->id,
                'modified_by_user_idx' => $admin->id,
            ]
        );
    }

    protected function ensureGateway(
        User $admin,
        Site $site,
        MeterLocation $location,
        string $gatewaySn,
        string $gatewayMac,
        string $gatewayIp,
        int $lastLogOffsetMinutes,
        int $updateRtu,
        int $updateRtuLocation,
        int $updateRtuSsh,
        int $updateRtuForceLp,
        string $softRev,
    ): Gateway {
        return Gateway::query()->updateOrCreate(
            [
                'site_idx' => $site->site_id,
                'gateway_sn' => $gatewaySn,
            ],
            [
                'location_idx' => $location->location_id,
                'site_code' => $site->site_code,
                'gateway_mac' => $gatewayMac,
                'gateway_ip' => $gatewayIp,
                'connection_type' => 'LAN',
                'gateway_description' => 'Phase 0 seeded gateway',
                'update_rtu' => $updateRtu,
                'update_rtu_location' => $updateRtuLocation,
                'update_rtu_ssh' => $updateRtuSsh,
                'update_rtu_force_lp' => $updateRtuForceLp,
                'created_by_user_idx' => $admin->id,
                'modified_by_user_idx' => $admin->id,
                'last_log_update' => CarbonImmutable::now()->subMinutes($lastLogOffsetMinutes)->toDateTimeString(),
                'soft_rev' => $softRev,
            ]
        );
    }

    protected function ensureMeter(
        User $admin,
        Site $site,
        Gateway $gateway,
        MeterLocation $location,
        Building $building,
        int $configId,
        string $meterName,
        string $siteCode,
        string $customerName,
        string $meterRole = 'Client Meter',
        string $loadProfile = 'NO',
        string $status = 'ACTIVE',
    ): Meter {
        return Meter::query()->updateOrCreate(
            [
                'site_idx' => $site->site_id,
                'rtu_idx' => $gateway->rtu_id,
                'meter_name' => $meterName,
            ],
            [
                'location_idx' => $location->location_id,
                'building_idx' => $building->building_id,
                'config_idx' => $configId,
                'site_code' => $siteCode,
                'meter_name_addressable' => 1,
                'meter_load_profile' => $loadProfile,
                'meter_default_name' => $meterName,
                'meter_type' => 'Smart Energy',
                'meter_brand' => 'S4E',
                'meter_role' => $meterRole,
                'meter_remarks' => 'Demo seeded meter',
                'customer_name' => $customerName,
                'meter_multiplier' => 1,
                'meter_status' => $status,
                'last_log_update' => CarbonImmutable::now()->subMinutes(15)->toDateTimeString(),
                'soft_rev' => '2.10',
                'created_by_user_idx' => $admin->id,
                'modified_by_user_idx' => $admin->id,
            ]
        );
    }

    protected function ensureUser(
        User $admin,
        string $name,
        string $email,
        string $realName,
        string $jobTitle,
        string $userType,
        string $userAccess,
    ): User {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'user_real_name' => $realName,
                'user_job_title' => $jobTitle,
                'user_type' => $userType,
                'user_access' => $userAccess,
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'created_by_user_idx' => $admin->id,
                'modified_by_user_idx' => $admin->id,
                'email_verified_at' => now(),
            ]
        );
    }

    protected function ensureUserSiteAccess(User $user, int $siteId, User $admin): UserSiteAccess
    {
        return UserSiteAccess::query()->updateOrCreate(
            [
                'user_idx' => (string) $user->id,
                'site_idx' => $siteId,
            ],
            [
                'user_name' => $user->name,
                'created_by_user_idx' => $admin->id,
                'updated_by_user_idx' => $admin->id,
                'access_list_src' => 'seed-profile',
            ]
        );
    }

    /**
     * @param  Collection<int, Meter>  $meters
     */
    protected function seedTelemetryHistory(Collection $meters, int $pointsPerMeter = 24, int $intervalMinutes = 15): void
    {
        if ($meters->isEmpty()) {
            return;
        }

        $endTime = CarbonImmutable::now()->startOfMinute();
        $startTime = $endTime->subMinutes($pointsPerMeter * $intervalMinutes);

        $meterLocationIds = $meters->pluck('location_idx')->unique()->filter()->values();
        $gatewayIds = $meters->pluck('rtu_idx')->unique()->values();

        $locationCodes = MeterLocation::query()
            ->whereIn('location_id', $meterLocationIds)
            ->pluck('location_code', 'location_id');

        $gatewayMacs = Gateway::query()
            ->whereIn('rtu_id', $gatewayIds)
            ->pluck('gateway_mac', 'rtu_id');

        $meterRows = [];
        $siteIds = [];
        $gatewayIdsToUpdate = [];

        foreach ($meters as $meterIndex => $meter) {
            $siteIds[] = $meter->site_idx;
            $gatewayIdsToUpdate[] = $meter->rtu_idx;
            $runningWhTotal = 100000 + (($meter->meter_id % 97) * 83);
            $runningWhRec = 30000 + (($meter->meter_id % 29) * 50);
            $runningWhDel = 22000 + (($meter->meter_id % 31) * 40);

            for ($step = 1; $step <= $pointsPerMeter; $step++) {
                $timestamp = $startTime->addMinutes($intervalMinutes * $step);
                $runningWhTotal += 45 + (($step + $meterIndex) % 12);
                $runningWhRec += 15 + (($step + $meterIndex) % 5);
                $runningWhDel += 15 + (($step + $meterIndex) % 5);

                $meterRows[] = [
                    'location' => (string) ($locationCodes[$meter->location_idx] ?? 'HOME'),
                    'meter_id' => (string) $meter->meter_id,
                    'datetime' => $timestamp->toDateTimeString(),
                    'vrms_a' => 227 + (($meterIndex + $step) % 4),
                    'vrms_b' => 225 + (($meterIndex * 2 + $step) % 5),
                    'vrms_c' => 226 + (($meterIndex * 3 + $step) % 4),
                    'irms_a' => 4 + (($meterIndex + $step) % 3),
                    'irms_b' => 4 + (($meterIndex + 2 * $step) % 3),
                    'irms_c' => 3 + (($meterIndex + 3 * $step) % 3),
                    'freq' => 59.9 + (($meterIndex % 4) * 0.02),
                    'pf' => 0.94 + (($meterIndex % 5) * 0.01),
                    'watt' => 900 + (($meterIndex * 11 + $step * 4) % 180),
                    'va' => 980 + (($meterIndex * 9 + $step * 3) % 170),
                    'var' => 40 + (($step + $meterIndex) % 11),
                    'wh_del' => $runningWhDel,
                    'wh_rec' => $runningWhRec,
                    'wh_net' => $runningWhRec - $runningWhDel,
                    'wh_total' => $runningWhTotal,
                    'varh_neg' => 30 + (($meterIndex + $step) % 11),
                    'varh_pos' => 45 + (($meterIndex + $step * 2) % 12),
                    'varh_net' => 75 + (($meterIndex + $step * 3) % 14),
                    'varh_total' => 120 + (($meterIndex + $step) % 17),
                    'vah_total' => 130 + (($meterIndex + $step * 2) % 9),
                    'max_rec_kw_dmd' => 1.02 + (($meterIndex + $step) % 5) * 0.1,
                    'max_rec_kw_dmd_time' => $timestamp->toDateTimeString(),
                    'max_del_kw_dmd' => 1.12 + (($meterIndex + $step) % 4) * 0.11,
                    'max_del_kw_dmd_time' => $timestamp->toDateTimeString(),
                    'max_pos_kvar_dmd' => 0.41 + (($meterIndex + $step) % 3) * 0.09,
                    'max_pos_kvar_dmd_time' => $timestamp->toDateTimeString(),
                    'max_neg_kvar_dmd' => 0.27 + (($meterIndex + $step) % 3) * 0.08,
                    'max_neg_kvar_dmd_time' => $timestamp->toDateTimeString(),
                    'v_ph_angle_a' => 0.1 + (($meterIndex + $step) % 3) * 0.01,
                    'v_ph_angle_b' => 0.2 + (($meterIndex + $step) % 3) * 0.02,
                    'v_ph_angle_c' => 0.3 + (($meterIndex + $step) % 3) * 0.03,
                    'i_ph_angle_a' => 1.1 + (($meterIndex + $step) % 3) * 0.02,
                    'i_ph_angle_b' => 1.2 + (($meterIndex + $step) % 3) * 0.02,
                    'i_ph_angle_c' => 1.3 + (($meterIndex + $step) % 3) * 0.02,
                    'mac_addr' => (string) ($gatewayMacs[$meter->rtu_idx] ?? ''),
                    'soft_rev' => (string) ($meter->soft_rev ?? '2.10'),
                    'relay_status' => $step % 2,
                    'dt' => $timestamp,
                    'genset_status' => (($step + $meterIndex) % 8 === 0) ? 1 : 0,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                if (count($meterRows) >= 250) {
                    MeterData::insert($meterRows);
                    $meterRows = [];
                }
            }
        }

        if ($meterRows !== []) {
            MeterData::insert($meterRows);
        }

        Meter::query()
            ->whereIn('meter_id', $meters->pluck('meter_id')->values())
            ->update(['last_log_update' => $endTime->toDateTimeString()]);

        Gateway::query()
            ->whereIn('rtu_id', $gatewayIdsToUpdate)
            ->update(['last_log_update' => $endTime->toDateTimeString()]);

        Site::query()
            ->whereIn('site_id', array_values(array_unique($siteIds)))
            ->update(['last_log_update' => $endTime->toDateTimeString()]);
    }
}
