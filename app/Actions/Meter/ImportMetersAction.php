<?php

namespace App\Actions\Meter;

use App\Models\ConfigurationFile;
use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterLocation;
use Illuminate\Http\UploadedFile;

final class ImportMetersAction
{
    public function execute(
        int $gatewayId,
        int $siteId,
        string $siteCode,
        UploadedFile $csvFile,
        int $modifiedByUserId,
    ): array {
        $lines = file($csvFile->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return [
                'error' => 'CSV File Error, please check the Content/Column Count.',
                'total_line' => 0,
                'result_csv_import' => 0,
            ];
        }

        $totalLines = count($lines);
        $resultCount = 0;

        foreach ($lines as $line) {
            $parts = preg_split('/[\t,]/', $line);

            if (count($parts) <= 10) {
                return [
                    'error' => 'CSV File Error, please check the Content/Column Count.',
                    'total_line' => 0,
                    'result_csv_import' => 0,
                ];
            }

            $locationCode = trim((string) ($parts[0] ?? ''));
            $meterName = trim((string) ($parts[1] ?? ''));
            $tenantName = trim((string) ($parts[2] ?? ''));
            $meterBrand = trim((string) ($parts[3] ?? ''));
            $meterType = trim((string) ($parts[4] ?? ''));
            $meterStatus = trim((string) ($parts[5] ?? ''));
            $configurationFile = trim((string) ($parts[6] ?? ''));
            $alternateAddress = trim((string) ($parts[7] ?? ''));
            $meterRole = trim((string) ($parts[8] ?? ''));
            $meterMultiplier = (float) trim((string) ($parts[9] ?? 0));
            $meterRemarks = trim((string) ($parts[10] ?? ''));

            if (strtoupper($meterStatus) === 'READ' || strtoupper($meterStatus) === 'ACTIVE') {
                $normalizedStatus = 'ACTIVE';
            } else {
                $normalizedStatus = 'INACTIVE';
            }

            if ($alternateAddress === '') {
                $meterDefaultName = $meterName;
                $meterNameAddressable = 1;
            } else {
                $meterDefaultName = $alternateAddress;
                $meterNameAddressable = 0;
            }

            $location = MeterLocation::query()->where('location_code', $locationCode)->first();
            $configuration = ConfigurationFile::query()->where('config_file', $configurationFile)->first();

            if (! $location || ! $configuration) {
                continue;
            }

            $existingMeter = Meter::query()
                ->where('meter_name', $meterName)
                ->where('site_idx', $siteId)
                ->first();

            if ($existingMeter !== null) {
                $existingMeter->update([
                    'site_idx' => $siteId,
                    'site_code' => $siteCode,
                    'rtu_idx' => $gatewayId,
                    'meter_name' => $meterName,
                    'meter_name_addressable' => $meterNameAddressable,
                    'meter_default_name' => $meterDefaultName,
                    'customer_name' => $tenantName,
                    'config_idx' => $configuration->config_id,
                    'meter_type' => $meterType,
                    'meter_brand' => $meterBrand,
                    'meter_multiplier' => $meterMultiplier,
                    'meter_role' => $meterRole,
                    'location_idx' => $location->location_id,
                    'meter_status' => $normalizedStatus,
                    'meter_remarks' => $meterRemarks,
                    'modified_by_user_idx' => $modifiedByUserId,
                ]);

                $resultCount++;

                continue;
            }

            Meter::query()->create([
                'site_idx' => $siteId,
                'site_code' => $siteCode,
                'rtu_idx' => $gatewayId,
                'location_idx' => $location->location_id,
                'building_idx' => 0,
                'config_idx' => $configuration->config_id,
                'meter_name' => $meterName,
                'meter_name_addressable' => $meterNameAddressable,
                'meter_load_profile' => 'NO',
                'meter_default_name' => $meterDefaultName,
                'meter_type' => $meterType,
                'meter_brand' => $meterBrand,
                'meter_role' => $meterRole,
                'meter_remarks' => $meterRemarks,
                'customer_name' => $tenantName,
                'meter_multiplier' => $meterMultiplier,
                'meter_status' => $normalizedStatus,
                'last_log_update' => '0000-00-00 00:00:00',
                'soft_rev' => 0,
                'created_by_user_idx' => $modifiedByUserId,
                'modified_by_user_idx' => $modifiedByUserId,
            ]);

            $resultCount++;
        }

        Gateway::query()->whereKey($gatewayId)->update(['update_rtu' => 1]);

        return [
            'success' => 'CSV File Successfully Imported!',
            'total_line' => $totalLines,
            'result_csv_import' => $resultCount,
        ];
    }
}
