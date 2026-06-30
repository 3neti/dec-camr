<?php

declare(strict_types=1);

namespace App\Actions\Gateway;

use App\Models\Gateway;
use App\Models\Meter;

final class DeleteGatewayAction
{
    public function execute(int $gatewayId): bool
    {
        $gateway = Gateway::query()->findOrFail($gatewayId);

        if (Meter::query()->where('rtu_idx', $gatewayId)->exists()) {
            return false;
        }

        return (bool) $gateway->delete();
    }
}
