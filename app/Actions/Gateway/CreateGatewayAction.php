<?php

namespace App\Actions\Gateway;

use App\Models\Gateway;

final class CreateGatewayAction
{
    public function execute(
        int $siteId,
        string $siteCode,
        int $locationId,
        string $gatewaySn,
        string $gatewayMac,
        string $gatewayIp,
        string $connectionType,
        int $createdByUserId,
        ?string $gatewayDescription = null,
    ): Gateway {
        return Gateway::query()->create([
            'site_idx' => $siteId,
            'site_code' => $siteCode,
            'location_idx' => $locationId,
            'gateway_sn' => $gatewaySn,
            'gateway_mac' => $gatewayMac,
            'gateway_ip' => $gatewayIp,
            'connection_type' => $connectionType,
            'gateway_description' => $gatewayDescription,
            'created_by_user_idx' => $createdByUserId,
            'update_rtu' => 0,
            'update_rtu_location' => 1,
            'update_rtu_ssh' => 0,
            'update_rtu_force_lp' => 0,
            'last_log_update' => '0000-00-00 00:00:00',
            'soft_rev' => 0,
        ]);
    }
}
