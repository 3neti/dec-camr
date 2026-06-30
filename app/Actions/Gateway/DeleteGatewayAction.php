<?php

namespace App\Actions\Gateway;

use App\Models\Gateway;

final class DeleteGatewayAction
{
    public function execute(int $gatewayId): bool
    {
        $gateway = Gateway::query()->findOrFail($gatewayId);

        return (bool) $gateway->delete();
    }
}
