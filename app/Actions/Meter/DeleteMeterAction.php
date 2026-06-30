<?php

namespace App\Actions\Meter;

use App\Models\Gateway;
use App\Models\Meter;

final class DeleteMeterAction
{
    public function execute(int $meterId): bool
    {
        $meter = Meter::query()
            ->findOrFail($meterId);

        $gatewayId = (int) $meter->rtu_idx;

        $deleted = (bool) $meter->delete();

        if ($deleted && $gatewayId > 0) {
            Gateway::query()->whereKey($gatewayId)->update(['update_rtu' => 1]);
        }

        return $deleted;
    }
}
