<?php

namespace App\Actions\Gateway;

use App\Models\Gateway;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class UpdateGatewayAction
{
    /**
     * @throws ModelNotFoundException
     */
    public function execute(
        int $gatewayId,
        int $locationId,
        string $gatewaySn,
        string $gatewayMac,
        string $gatewayIp,
        string $connectionType,
        int $modifiedByUserId,
        ?string $gatewayDescription = null,
        ?string $siteCode = null,
    ): Gateway {
        $gateway = Gateway::query()->findOrFail($gatewayId);

        $gateway->location_idx = $locationId;
        $gateway->gateway_sn = $gatewaySn;
        $gateway->gateway_mac = $gatewayMac;
        $gateway->gateway_ip = $gatewayIp;
        $gateway->connection_type = $connectionType;
        $gateway->gateway_description = $gatewayDescription;

        if ($siteCode !== null) {
            $gateway->site_code = $siteCode;
        }

        $gateway->modified_by_user_idx = $modifiedByUserId;

        $gateway->update();

        return $gateway;
    }
}
