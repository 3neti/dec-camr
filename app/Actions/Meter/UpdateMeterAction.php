<?php

namespace App\Actions\Meter;

use App\Models\Gateway;
use App\Models\Meter;

final class UpdateMeterAction
{
    public function execute(
        int $meterId,
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
        int $modifiedByUserId,
    ): Meter {
        $meter = Meter::query()->findOrFail($meterId);

        $meter->site_idx = $siteId;
        $meter->site_code = $siteCode;
        $meter->meter_name = $meterName;
        $meter->meter_name_addressable = $meterNameAddressable;
        $meter->meter_default_name = $meterDefaultName;
        $meter->config_idx = $configId;
        $meter->rtu_idx = $rtuId;
        $meter->location_idx = $locationId;
        $meter->customer_name = $customerName;
        $meter->meter_type = $meterType;
        $meter->meter_brand = $meterBrand;
        $meter->meter_multiplier = $meterMultiplier;
        $meter->meter_role = $meterRole;
        $meter->meter_status = $meterStatus;
        $meter->meter_remarks = $meterRemarks;
        $meter->modified_by_user_idx = $modifiedByUserId;

        $meter->update();

        Gateway::query()->whereKey($rtuId)->update(['update_rtu' => 1]);

        return $meter;
    }
}
