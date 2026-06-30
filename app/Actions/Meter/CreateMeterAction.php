<?php

namespace App\Actions\Meter;

use App\Models\Gateway;
use App\Models\Meter;

final class CreateMeterAction
{
    public function execute(
        int $siteId,
        string $siteCode,
        string $meterName,
        int $meterNameAddressable,
        string $meterDefaultName,
        int $configId,
        int $rtuId,
        int $locationId,
        string $customerName,
        string $meterType,
        string $meterBrand,
        int $meterMultiplier,
        string $meterRole,
        string $meterStatus,
        ?string $meterRemarks,
        int $createdByUserId,
    ): Meter {
        $meter = Meter::query()->create([
            'site_idx' => $siteId,
            'site_code' => $siteCode,
            'meter_name' => $meterName,
            'meter_name_addressable' => $meterNameAddressable,
            'meter_default_name' => $meterDefaultName,
            'config_idx' => $configId,
            'rtu_idx' => $rtuId,
            'location_idx' => $locationId,
            'building_idx' => 0,
            'meter_type' => $meterType,
            'meter_brand' => $meterBrand,
            'meter_multiplier' => $meterMultiplier,
            'meter_role' => $meterRole,
            'meter_status' => $meterStatus,
            'customer_name' => $customerName,
            'meter_remarks' => $meterRemarks,
            'meter_load_profile' => 'NO',
            'last_log_update' => '0000-00-00 00:00:00',
            'soft_rev' => 0,
            'created_by_user_idx' => $createdByUserId,
            'modified_by_user_idx' => $createdByUserId,
        ]);

        Gateway::query()
            ->whereKey($rtuId)
            ->update(['update_rtu' => 1]);

        return $meter;
    }
}
