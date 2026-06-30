<?php

declare(strict_types=1);

namespace App\Actions\Meter;

use App\Models\Gateway;
use App\Models\Meter;
use Illuminate\Support\Facades\DB;

final class DeleteMeterAction
{
    public function execute(int $meterId): bool
    {
        $meter = Meter::query()->findOrFail($meterId);

        $gatewayId = (int) $meter->rtu_idx;

        return DB::transaction(function () use ($meter, $gatewayId): bool {
            $deleted = (bool) $meter->delete();

            if (! $deleted) {
                return false;
            }

            if ($gatewayId > 0) {
                Gateway::query()->whereKey($gatewayId)->update(['update_rtu' => 1]);
            }

            return true;
        });
    }
}
