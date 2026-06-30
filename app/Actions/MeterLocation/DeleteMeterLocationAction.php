<?php

declare(strict_types=1);

namespace App\Actions\MeterLocation;

use App\Models\Meter;
use App\Models\MeterLocation;

final class DeleteMeterLocationAction
{
    public function execute(int $meterLocationId): bool
    {
        $location = MeterLocation::query()->find($meterLocationId);

        if ($location === null) {
            return false;
        }

        if (Meter::query()->where('location_idx', $meterLocationId)->exists()) {
            return false;
        }

        return (bool) $location->delete();
    }
}
